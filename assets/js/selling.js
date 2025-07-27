/**
 * Client-seitige Logik für den Fahrzeugverkauf
 */

class SellingManager {
    constructor() {
        this.photos = [];
        this.vehicleData = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadStoredData();
    }

    bindEvents() {
        // Foto-Upload Events
        const photoUploadArea = document.getElementById('photoUploadArea');
        const photoInput = document.getElementById('photoInput');
        
        if (photoUploadArea && photoInput) {
            photoUploadArea.addEventListener('click', () => photoInput.click());
            photoUploadArea.addEventListener('dragover', this.handleDragOver.bind(this));
            photoUploadArea.addEventListener('drop', this.handleDrop.bind(this));
            photoInput.addEventListener('change', this.handlePhotoSelect.bind(this));
        }

        // Formular Events
        const conditionForm = document.getElementById('conditionForm');
        if (conditionForm) {
            conditionForm.addEventListener('submit', this.handleConditionSubmit.bind(this));
            conditionForm.addEventListener('input', this.validateForm.bind(this));
        }

        // Ergebnis-Seite Events
        const createListingBtn = document.getElementById('createListingBtn');
        const generateContractBtn = document.getElementById('generateContractBtn');
        const saveDraftBtn = document.getElementById('saveDraftBtn');

        if (createListingBtn) createListingBtn.addEventListener('click', this.createListing.bind(this));
        if (generateContractBtn) generateContractBtn.addEventListener('click', this.generateContract.bind(this));
        if (saveDraftBtn) saveDraftBtn.addEventListener('click', this.saveDraft.bind(this));

        // Share Buttons
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', this.handleShare.bind(this));
        });

        // Checklist Items
        document.querySelectorAll('.checklist-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', this.updateChecklistProgress.bind(this));
        });
    }

    loadStoredData() {
        // Geladene Daten aus Session Storage
        const storedData = sessionStorage.getItem('sellingData');
        if (storedData) {
            this.vehicleData = JSON.parse(storedData);
        }

        // Wenn wir auf der Ergebnis-Seite sind, Preis laden
        if (window.location.pathname.includes('result_page.php')) {
            this.loadPriceEstimate();
        }
    }

    handleDragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
    }

    handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        this.processPhotos(files);
    }

    handlePhotoSelect(e) {
        const files = Array.from(e.target.files);
        this.processPhotos(files);
    }

    processPhotos(files) {
        const validFiles = files.filter(file => 
            file.type.startsWith('image/') && file.size <= 10 * 1024 * 1024
        );

        validFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.photos.push(e.target.result);
                this.updatePhotoPreview();
                this.validateForm();
            };
            reader.readAsDataURL(file);
        });

        if (validFiles.length !== files.length) {
            alert('Einige Dateien wurden übersprungen. Bitte nur Bilder bis 10MB verwenden.');
        }
    }

    updatePhotoPreview() {
        const preview = document.getElementById('photoPreview');
        if (!preview) return;

        preview.innerHTML = '';
        
        this.photos.forEach((photo, index) => {
            const photoDiv = document.createElement('div');
            photoDiv.className = 'photo-item';
            photoDiv.innerHTML = `
                <img src="${photo}" alt="Fahrzeugfoto ${index + 1}">
                <button type="button" class="remove-photo" data-index="${index}">&times;</button>
            `;
            preview.appendChild(photoDiv);
        });

        // Remove-Events binden
        document.querySelectorAll('.remove-photo').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.photos.splice(index, 1);
                this.updatePhotoPreview();
                this.validateForm();
            });
        });
    }

    validateForm() {
        const form = document.getElementById('conditionForm');
        if (!form) return;

        const requiredFields = form.querySelectorAll('[required]');
        const submitBtn = document.getElementById('submitBtn');
        
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value) isValid = false;
        });

        if (this.photos.length < 4) isValid = false;

        if (submitBtn) {
            submitBtn.disabled = !isValid;
        }
    }

    async handleConditionSubmit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const conditionData = {
            make: this.vehicleData.make,
            model: this.vehicleData.model,
            year: this.vehicleData.year,
            mileage: this.vehicleData.mileage,
            condition: formData.get('condition'),
            photos: this.photos,
            service_history: formData.get('service_history'),
            accidents: formData.get('accidents'),
            tires_summer: formData.get('tires_summer'),
            tires_winter: formData.get('tires_winter'),
            description: formData.get('description')
        };

        const submitBtn = document.getElementById('submitBtn');
        const spinner = submitBtn.querySelector('.loading-spinner');
        
        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';

        try {
            const response = await fetch('/api/estimate_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(conditionData)
            });

            const result = await response.json();

            if (response.ok) {
                // Daten für Ergebnis-Seite speichern
                sessionStorage.setItem('priceEstimate', JSON.stringify(result));
                sessionStorage.setItem('conditionData', JSON.stringify(conditionData));
                
                // Weiterleitung zur Ergebnis-Seite
                window.location.href = '/templates/sell_vehicle/result_page.php';
            } else {
                throw new Error(result.error || 'Fehler bei der Preisberechnung');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Fehler: ' + error.message);
        } finally {
            submitBtn.disabled = false;
            spinner.style.display = 'none';
        }
    }

    async loadPriceEstimate() {
        const estimate = JSON.parse(sessionStorage.getItem('priceEstimate') || '{}');
        
        if (!estimate.estimated_price) {
            window.location.href = '/templates/sell_vehicle/condition_form.php';
            return;
        }

        // Preise anzeigen
        document.getElementById('estimatedPrice').textContent = 
            new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(estimate.estimated_price);
        document.getElementById('minPrice').textContent = 
            new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(estimate.price_range.min);
        document.getElementById('maxPrice').textContent = 
            new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(estimate.price_range.max);

        // Analyse anzeigen
        this.displayAnalysis(estimate.analysis);

        // Empfehlungen anzeigen
        this.displayRecommendations(estimate.recommendations);
    }

    displayAnalysis(analysis) {
        const grid = document.getElementById('analysisGrid');
        if (!grid || !analysis) return;

        grid.innerHTML = `
            <div class="analysis-item">
                <span class="analysis-label">Marktwert</span>
                <span class="analysis-value">${new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(analysis.base_price)}</span>
            </div>
            <div class="analysis-item">
                <span class="analysis-label">Zustand</span>
                <span class="analysis-value">${(analysis.condition_factor * 100).toFixed(0)}%</span>
            </div>
            <div class="analysis-item">
                <span class="analysis-label">Fotos</span>
                <span class="analysis-value">${analysis.photo_quality}% Qualität</span>
            </div>
            <div class="analysis-item">
                <span class="analysis-label">Schadensfaktor</span>
                <span class="analysis-value">${(analysis.damage_factor * 100).toFixed(0)}%</span>
            </div>
        `;
    }

    displayRecommendations(recommendations) {
        const list = document.getElementById('recommendationList');
        if (!list) return;

        list.innerHTML = '';
        
        if (recommendations && recommendations.length > 0) {
            recommendations.forEach(rec => {
                const li = document.createElement('li');
                li.textContent = rec;
                list.appendChild(li);
            });
        } else {
            list.innerHTML = '<li>Ihr Fahrzeug ist optimal für den Verkauf vorbereitet!</li>';
        }
    }

    async createListing() {
        const conditionData = JSON.parse(sessionStorage.getItem('conditionData') || '{}');
        const priceEstimate = JSON.parse(sessionStorage.getItem('priceEstimate') || '{}');

        try {
            const response = await fetch('/api/create_listing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...conditionData,
                    price: priceEstimate.estimated_price
                })
            });

            const result = await response.json();

            if (response.ok) {
                alert('Inserat erfolgreich erstellt!');
                sessionStorage.removeItem('sellingData');
                sessionStorage.removeItem('priceEstimate');
                sessionStorage.removeItem('conditionData');
                window.location.href = '/dashboard.php';
            } else {
                throw new Error(result.error || 'Fehler beim Erstellen des Inserats');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Fehler: ' + error.message);
        }
    }

    async generateContract() {
        const buyerName = prompt('Name des Käufers:');
        const buyerAddress = prompt('Adresse des Käufers:');

        if (!buyerName || !buyerAddress) return;

        const vehicleId = JSON.parse(sessionStorage.getItem('sellingData') || '{}').vehicle_id;
        const priceEstimate = JSON.parse(sessionStorage.getItem('priceEstimate') || '{}');

        try {
            const response = await fetch('/api/generate_contract.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    vehicle_id: vehicleId,
                    sale_price: priceEstimate.estimated_price,
                    buyer_name: buyerName,
                    buyer_address: buyerAddress
                })
            });

            const result = await response.json();

            if (response.ok) {
                // Vertrag herunterladen
                const blob = new Blob([result.contract_text], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `kaufvertrag_${vehicleId}.txt`;
                a.click();
                URL.revokeObjectURL(url);
            } else {
                throw new Error(result.error || 'Fehler beim Erstellen des Vertrags');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Fehler: ' + error.message);
        }
    }

    async saveDraft() {
        const conditionData = JSON.parse(sessionStorage.getItem('conditionData') || '{}');
        const priceEstimate = JSON.parse(sessionStorage.getItem('priceEstimate') || '{}');

        try {
            const response = await fetch('/api/save_draft.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...conditionData,
                    price: priceEstimate.estimated_price
                })
            });

            const result = await response.json();

            if (response.ok) {
                alert('Entwurf gespeichert!');
            } else {
                throw new Error(result.error || 'Fehler beim Speichern des Entwurfs');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Fehler: ' + error.message);
        }
    }

    handleShare(e) {
        const platform = e.currentTarget.dataset.platform;
        const url = window.location.origin + '/listing/' + JSON.parse(sessionStorage.getItem('sellingData') || '{}').vehicle_id;
        const text = 'Schau dir mein Fahrzeug auf Carfify an!';

        switch (platform) {
            case 'facebook':
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
                break;
            case 'whatsapp':
                window.open(`https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`, '_blank');
                break;
            case 'email':
                window.location.href = `mailto:?subject=${encodeURIComponent('Fahrzeug auf Carfify')}&body=${encodeURIComponent(text + '\n' + url)}`;
                break;
        }
    }

    updateChecklistProgress() {
        const checkboxes = document.querySelectorAll('.checklist-item input[type="checkbox"]');
        const checked = document.querySelectorAll('.checklist-item input[type="checkbox"]:checked');
        
        const progress = (checked.length / checkboxes.length) * 100;
        
        // Optional: Progress-Bar aktualisieren
        const progressBar = document.querySelector('.checklist-progress');
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
    }
}

// Initialisierung
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new SellingManager());
} else {
    new SellingManager();
}
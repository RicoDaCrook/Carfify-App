// Verkaufs-Funktionalität

let sellingSession = {
    vehicleId: null,
    mileage: null,
    condition: {},
    images: [],
    priceEstimate: null
};

function startSelling() {
    showModal('selling-modal');
    loadSellingStep('condition');
}

function loadSellingStep(step) {
    switch(step) {
        case 'condition':
            loadConditionForm();
            break;
        case 'photos':
            loadPhotoUpload();
            break;
        case 'price':
            loadPriceEstimate();
            break;
        case 'result':
            loadSellingResult();
            break;
    }
}

function loadConditionForm() {
    const content = document.getElementById('selling-content');
    
    fetch('templates/sell_vehicle/condition_form.php')
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            
            // Form-Handler
            document.getElementById('condition-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                sellingSession.vehicleId = document.getElementById('vehicle-select').value;
                sellingSession.mileage = parseInt(document.getElementById('mileage').value);
                sellingSession.condition = {
                    accident_free: document.getElementById('accident-free').checked,
                    service_history: document.getElementById('service-history').checked,
                    first_owner: document.getElementById('first-owner').checked,
                    non_smoker: document.getElementById('non-smoker').checked
                };
                
                loadSellingStep('photos');
            });
        });
}

function loadPhotoUpload() {
    const content = document.getElementById('selling-content');
    
    content.innerHTML = `
        <h3>Fahrzeugfotos hochladen</h3>
        <p>Bitte laden Sie Fotos von folgenden Perspektiven hoch:</p>
        
        <div class="photo-upload-section">
            <div class="photo-upload" data-type="front">
                <label>Frontansicht</label>
                <input type="file" accept="image/*" onchange="handlePhotoUpload(this, 'front')">
                <img id="preview-front" style="display:none; max-width:200px;">
            </div>
            
            <div class="photo-upload" data-type="back">
                <label>Heckansicht</label>
                <input type="file" accept="image/*" onchange="handlePhotoUpload(this, 'back')">
                <img id="preview-back" style="display:none; max-width:200px;">
            </div>
            
            <div class="photo-upload" data-type="side">
                <label>Seitenansicht</label>
                <input type="file" accept="image/*" onchange="handlePhotoUpload(this, 'side')">
                <img id="preview-side" style="display:none; max-width:200px;">
            </div>
            
            <div class="photo-upload" data-type="interior">
                <label>Innenraum</label>
                <input type="file" accept="image/*" onchange="handlePhotoUpload(this, 'interior')">
                <img id="preview-interior" style="display:none; max-width:200px;">
            </div>
            
            <div class="photo-upload" data-type="dashboard">
                <label>Tacho</label>
                <input type="file" accept="image/*" onchange="handlePhotoUpload(this, 'dashboard')">
                <img id="preview-dashboard" style="display:none; max-width:200px;">
            </div>
        </div>
        
        <button onclick="analyzePhotos()" id="analyze-btn" disabled>Fotos analysieren</button>
    `;
}

function handlePhotoUpload(input, type) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(`preview-${type}`).src = e.target.result;
            document.getElementById(`preview-${type}`).style.display = 'block';
            
            // Speichere Bild-URL
            sellingSession.images.push({
                type: type,
                url: e.target.result
            });
            
            // Aktiviere Analyse-Button wenn alle Bilder hochgeladen
            if (sellingSession.images.length >= 3) {
                document.getElementById('analyze-btn').disabled = false;
            }
        };
        reader.readAsDataURL(file);
    }
}

function analyzePhotos() {
    // Simulierte Bildanalyse
    // In Produktion würde hier die Image Analysis API aufgerufen
    const mockAnalysis = {
        damages: [
            { type: 'scratch', location: 'front_bumper', severity: 'minor' },
            { type: 'dent', location: 'left_door', severity: 'minor' }
        ],
        overall_condition: 'good'
    };
    
    sellingSession.imageAnalysis = mockAnalysis;
    loadSellingStep('price');
}

function loadPriceEstimate() {
    const content = document.getElementById('selling-content');
    content.innerHTML = '<p>Analysiere Marktpreise...</p>';
    
    const formData = new FormData();
    formData.append('vehicle_id', sellingSession.vehicleId);
    formData.append('mileage', sellingSession.mileage);
    formData.append('condition', JSON.stringify(sellingSession.condition));
    formData.append('image_analysis', JSON.stringify(sellingSession.imageAnalysis));
    
    fetch('api/estimate_price.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        sellingSession.priceEstimate = data;
        loadSellingStep('result');
    });
}

function loadSellingResult() {
    const content = document.getElementById('selling-content');
    
    fetch('templates/sell_vehicle/result_page.php')
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            
            // Fülle Preisschätzung
            document.getElementById('price-range').textContent = 
                `${sellingSession.priceEstimate.estimated_price.min}€ - ${sellingSession.priceEstimate.estimated_price.max}€`;
            document.getElementById('average-price').textContent = 
                `${sellingSession.priceEstimate.estimated_price.average}€`;
            
            // Generiere Checkliste
            generateChecklist();
        });
}

function generateChecklist() {
    const checklist = document.getElementById('selling-checklist');
    const items = [
        'Fahrzeug gründlich reinigen (innen & außen)',
        'Alle wichtigen Dokumente zusammenstellen (Zulassungsbescheinigung, Serviceheft, Rechnungen)',
        'TÜV/AU-Prüfung ggf. neu machen',
        'Kleine Mängel beheben',
        'Anzeige mit allen Details erstellen',
        'Probefahrten organisieren',
        'Kaufvertrag vorbereiten'
    ];
    
    items.forEach(item => {
        const li = document.createElement('li');
        li.innerHTML = `<label><input type="checkbox"> ${item}</label>`;
        checklist.appendChild(li);
    });
}

function generateContract() {
    const formData = new FormData();
    formData.append('vehicle_id', sellingSession.vehicleId);
    formData.append('price', sellingSession.priceEstimate.estimated_price.average);
    formData.append('seller_data', JSON.stringify({
        name: 'Max Mustermann',
        address: 'Musterstraße 1, 12345 Musterstadt',
        phone: '01234-567890'
    }));
    
    fetch('api/generate_contract.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Öffne Kaufvertrag in neuem Tab
        const newWindow = window.open();
        newWindow.document.write(data.contract_html);
    });
}
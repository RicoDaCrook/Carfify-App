// Carfify Fahrzeugverkauf JavaScript

let sellingData = {
    vehicle_id: null,
    mileage: 0,
    condition: {},
    images: [],
    images_analysis: {}
};

// Verkaufsprozess starten
function startSelling() {
    openModal('selling-modal');
    loadSellingForm();
}

// Verkaufsformular laden
function loadSellingForm() {
    fetch('templates/sell_vehicle/condition_form.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('selling-content').innerHTML = html;
            setupSellingForm();
            setupImageUpload();
        });
}

// Formular-Setup
function setupSellingForm() {
    const form = document.getElementById('condition-form');
    if (form) {
        form.addEventListener('submit', handleConditionSubmit);
    }
}

// Bild-Upload Setup
function setupImageUpload() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('image-upload');
    const preview = document.getElementById('image-preview');
    
    if (!uploadArea || !fileInput) return;
    
    // Drag & Drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

// Dateien verarbeiten
function handleFiles(files) {
    const preview = document.getElementById('image-preview');
    
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'preview-image';
            
            const container = document.createElement('div');
            container.className = 'image-container';
            container.appendChild(img);
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-image';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = () => {
                container.remove();
                sellingData.images = sellingData.images.filter(img => img !== e.target.result);
            };
            
            container.appendChild(removeBtn);
            preview.appendChild(container);
            
            sellingData.images.push(e.target.result);
        };
        reader.readAsDataURL(file);
    });
    
    // Bildanalyse simulieren
    simulateImageAnalysis();
}

// Bildanalyse simulieren
function simulateImageAnalysis() {
    // In echter Implementierung würde hier eine KI-Analyse stattfinden
    setTimeout(() => {
        sellingData.images_analysis = {
            damages: [
                { type: 'scratch', location: 'front_bumper', severity: 'minor' },
                { type: 'dent', location: 'rear_door', severity: 'minor' }
            ],
            overall_condition: 'good',
            paint_quality: 'excellent'
        };
        
        showNotification('Bildanalyse abgeschlossen', 'success');
    }, 2000);
}

// Formular absenden
function handleConditionSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Daten sammeln
    sellingData.mileage = parseInt(formData.get('mileage'));
    sellingData.condition = {
        accident_free: formData.get('accident_free') === '1',
        service_history: formData.get('service_history') === '1',
        first_owner: formData.get('first_owner') === '1',
        non_smoker: formData.get('non_smoker') === '1',
        additional_info: formData.get('additional_info')
    };
    
    // Preis schätzen
    estimatePrice();
}

// Preisschätzung
function estimatePrice() {
    const payload = {
        vehicle_id: sellingData.vehicle_id || 1, // Standard-ID für Demo
        mileage: sellingData.mileage,
        condition_report: JSON.stringify(sellingData.condition),
        images_analysis: sellingData.images_analysis
    };
    
    fetch('api/estimate_price.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSellingResult(data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Preisschätzung fehlgeschlagen', 'error');
    });
}

// Ergebnis anzeigen
function showSellingResult(data) {
    fetch('templates/sell_vehicle/result_page.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('selling-content').innerHTML = html;
            
            // Preis anzeigen
            document.getElementById('min-price').textContent = formatCurrency(data.price_range.min);
            document.getElementById('max-price').textContent = formatCurrency(data.price_range.max);
            
            // Checkliste generieren
            generateChecklist(data);
        });
}

// Checkliste generieren
function generateChecklist(data) {
    const checklist = document.getElementById('checklist-items');
    const items = [
        'Fahrzeug gründlich reinigen (Innen & Außen)',
        'Alle Fahrzeugdokumente zusammenstellen',
        'HU/AU Bericht prüfen und ggf. erneuern',
        'Serviceheft vervollständigen',
        'Zweitschlüssel und Bordliteratur bereitstellen',
        'Rechnungen für Wartungen/Reparaturen sammeln',
        'Fahrzeugbilder für Inserat erstellen',
        'Kaufvertrag vorbereiten',
        'Probefahrtbedingungen festlegen',
        'Preisverhandlungsspielraum definieren'
    ];
    
    items.forEach(item => {
        const li = document.createElement('li');
        li.innerHTML = `
            <input type="checkbox" onchange="toggleChecklistItem(this)">
            <span>${item}</span>
        `;
        checklist.appendChild(li);
    });
}

// Checklist-Item umschalten
function toggleChecklistItem(checkbox) {
    const li = checkbox.closest('li');
    if (checkbox.checked) {
        li.classList.add('completed');
    } else {
        li.classList.remove('completed');
    }
}

// Kaufvertrag generieren
function generateContract() {
    const contractData = {
        vehicle_id: sellingData.vehicle_id || 1,
        seller_info: {
            name: 'Max Mustermann',
            address: 'Musterstraße 1, 12345 Musterstadt',
            phone: '0123 4567890'
        },
        buyer_info: {
            name: '', // Wird vom Nutzer ausgefüllt
            address: '',
            phone: ''
        },
        price: 15000, // Geschätzter Preis
        vin: 'W0L000051T2123456',
        mileage: sellingData.mileage
    };
    
    fetch('api/generate_contract.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(contractData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.open(data.download_url, '_blank');
            showNotification('Kaufvertrag wurde generiert', 'success');
        }
    });
}

// Checkliste downloaden
function downloadChecklist() {
    const checklist = document.getElementById('checklist-items');
    const items = Array.from(checklist.querySelectorAll('li')).map(li => 
        li.querySelector('span').textContent
    );
    
    const content = `Carfify Verkaufs-Checkliste\n\n${items.map((item, i) => `${i + 1}. ${item}`).join('\n')}`;
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'verkaufs-checkliste.txt';
    a.click();
    URL.revokeObjectURL(url);
}
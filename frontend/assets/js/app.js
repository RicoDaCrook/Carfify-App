/**
 * Carfify - Frontend JavaScript
 * Intelligente KFZ-Diagnose Web-App
 */

// API Configuration - anpassen für Vercel
const API_BASE = window.location.origin.includes('vercel.app') 
    ? 'https://' + window.location.hostname + '/backend/api'
    : 'http://localhost:8000/backend/api';

// DOM Elemente
const form = document.getElementById('diagnose-form');
const resultDiv = document.getElementById('diagnose-result');
const loadingDiv = document.getElementById('loading');
const errorDiv = document.getElementById('error');

// Utility Functions
const showLoading = () => {
    loadingDiv.style.display = 'block';
    resultDiv.style.display = 'none';
    errorDiv.style.display = 'none';
};

const hideLoading = () => {
    loadingDiv.style.display = 'none';
};

const showError = (message) => {
    errorDiv.innerHTML = `
        <div class="alert alert-danger">
            <strong>Fehler:</strong> ${message}
        </div>
    `;
    errorDiv.style.display = 'block';
    hideLoading();
};

const showSuccess = (data) => {
    const { diagnosis, confidence, solutions } = data;
    
    let solutionsHTML = '';
    solutions.forEach((solution, index) => {
        solutionsHTML += `
            <div class="solution-card">
                <h4>Lösung ${index + 1}: ${solution.type}</h4>
                <p><strong>Beschreibung:</strong> ${solution.description}</p>
                <p><strong>Kosten:</strong> ${solution.estimatedCost}</p>
                <p><strong>Zeit:</strong> ${solution.estimatedTime}</p>
                ${solution.parts ? `<p><strong>Benötigte Teile:</strong> ${solution.parts.join(', ')}</p>` : ''}
            </div>
        `;
    });

    resultDiv.innerHTML = `
        <div class="result-card">
            <h3>Diagnose-Ergebnis</h3>
            <p><strong>Festgestelltes Problem:</strong> ${diagnosis}</p>
            <p><strong>Sicherheit:</strong> ${confidence}%</p>
            ${solutionsHTML}
        </div>
    `;
    resultDiv.style.display = 'block';
};

// Form Submission Handler
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const symptom = formData.get('symptom');
        const symptomDetail = formData.get('symptom-detail');
        const carMake = formData.get('car-make');
        const carModel = formData.get('car-model');
        const carYear = formData.get('car-year');
        
        // Validation
        if (!symptom || !carMake || !carModel) {
            showError('Bitte füllen Sie mindestens Symptom, Marke und Modell aus.');
            return;
        }
        
        showLoading();
        
        try {
            const response = await fetch(`${API_BASE}/diagnose.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    symptom,
                    symptomDetail,
                    car: {
                        make: carMake,
                        model: carModel,
                        year: carYear ? parseInt(carYear) : null
                    }
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP-Fehler! Status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                showSuccess(data.data);
            } else {
                showError(data.message || 'Diagnose fehlgeschlagen');
            }
            
        } catch (error) {
            console.error('Fehler:', error);
            showError('Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung.');
        }
    });
}

// Dynamische Felder basierend auf Symptom-Auswahl
document.addEventListener('DOMContentLoaded', () => {
    const symptomSelect = document.getElementById('symptom');
    const symptomDetailDiv = document.getElementById('symptom-detail-container');
    
    if (symptomSelect && symptomDetailDiv) {
        symptomSelect.addEventListener('change', (e) => {
            const selectedSymptom = e.target.value;
            const details = SYMPTOM_DETAILS[selectedSymptom] || [];
            
            if (details.length > 0) {
                symptomDetailDiv.style.display = 'block';
                const detailSelect = document.getElementById('symptom-detail');
                detailSelect.innerHTML = '<option value="">-- Bitte wählen --</option>';
                
                details.forEach(detail => {
                    const option = document.createElement('option');
                    option.value = detail.toLowerCase().replace(/\s+/g, '-');
                    option.textContent = detail;
                    detailSelect.appendChild(option);
                });
            } else {
                symptomDetailDiv.style.display = 'none';
            }
        });
    }
    
    // Smooth scrolling bei Anker-Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Symptom-Konfiguration
const SYMPTOM_DETAILS = {
    'engine': [
        'Rasseln beim Start',
        'Ruckeln beim Fahren',
        'Leistungsverlust',
        'Anlasser springt nicht an',
        'Stalls/Abstellen'
    ],
    'brakes': [
        'Quietschen',
        'Schleifer',
        'Schwaches Bremsen',
        'Rucken beim Bremsen',
        'ABS-Leuchte'
    ],
    'transmission': [
        'Schaltknarren',
        'Ungewolltes Schalten',
        'Schaltprobleme',
        'Getriebe rutscht'
    ],
    'electrical': [
        'Batterie-Ausfall',
        'Licht versagt',
        'Startprobleme',
        'Hupen geht nicht'
    ],
    'suspension': [
        'Vibrieren bei hoher Geschwindigkeit',
        'Übersteuerung',
        'Untersteuerung',
        'Scheppern'
    ]
};

// Progressive Enhancement
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/frontend/assets/js/sw.js')
            .then(registration => console.log('SW registered'))
            .catch(registrationError => console.log('SW registration failed'));
    });
}

// Loading Animation
const loadingAnimation = () => {
    const dots = document.querySelectorAll('.loading-dot');
    dots.forEach((dot, index) => {
        setTimeout(() => {
            dot.style.animationPlayState = 'running';
        }, index * 100);
    });
};

// Re-init loading animation when shown
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' && 
            mutation.attributeName === 'style' && 
            mutation.target.style.display === 'block') {
            loadingAnimation();
        }
    });
});

if (loadingDiv) {
    observer.observe(loadingDiv, { attributes: true });
}

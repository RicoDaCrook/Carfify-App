/**
 * Carfify Frontend Application
 * Haupt-JavaScript-Datei für die intelligente KFZ-Diagnose
 */

// Globale App-Konfiguration
const APP_CONFIG = {
    API_BASE_URL: '/backend/api',
    LOCALE: 'de-DE',
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    SUPPORTED_IMAGE_TYPES: ['image/jpeg', 'image/png', 'image/webp'],
    SESSION_STORAGE_KEY: 'carfify_diagnosis_session',
    AUTO_SAVE_INTERVAL: 5000 // 5 Sekunden
};

// App-Status-Manager
class AppState {
    constructor() {
        this.currentStep = 0;
        this.totalSteps = 5;
        this.vehicleInfo = {};
        this.diagnosisSession = {};
        this.sessionId = null;
        this.autoSaveInterval = null;
        this.init();
    }

    init() {
        // Lade gespeicherte Session
        this.loadSavedSession();
        
        // Auto-Speichern starten
        this.startAutoSave();
        
        // Progress-Bar initialisieren
        this.updateProgressBar();
    }

    loadSavedSession() {
        try {
            const saved = sessionStorage.getItem(APP_CONFIG.SESSION_STORAGE_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                this.diagnosisSession = parsed.diagnosis || {};
                this.vehicleInfo = parsed.vehicle || {};
                this.currentStep = parsed.currentStep || 0;
                this.sessionId = parsed.sessionId || null;
            }
        } catch (error) {
            console.warn('Konnte gespeicherte Session nicht laden:', error);
            this.clearSession();
        }
    }

    saveSession() {
        try {
            const data = {
                diagnosis: this.diagnosisSession,
                vehicle: this.vehicleInfo,
                currentStep: this.currentStep,
                sessionId: this.sessionId,
                timestamp: Date.now()
            };
            sessionStorage.setItem(APP_CONFIG.SESSION_STORAGE_KEY, JSON.stringify(data));
            
            // Optional: Server-Backup
            if (this.sessionId) {
                this.backupToServer();
            }
        } catch (error) {
            console.error('Fehler beim Speichern:', error);
        }
    }

    clearSession() {
        sessionStorage.removeItem(APP_CONFIG.SESSION_STORAGE_KEY);
        this.currentStep = 0;
        this.vehicleInfo = {};
        this.diagnosisSession = {};
        this.sessionId = null;
    }

    updateProgressBar() {
        const progressBar = document.getElementById('progress-bar');
        if (progressBar) {
            const percentage = (this.currentStep / this.totalSteps) * 100;
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', this.currentStep);
            
            // Spezialeffekt bei 100%
            if (percentage === 100) {
                progressBar.classList.add('progress-complete');
            }
        }
    }

    async backupToServer() {
        try {
            const response = await fetch(`${APP_CONFIG.API_BASE_URL}/session.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'autosave',
                    sessionId: this.sessionId,
                    data: this.diagnosisSession
                })
            });
            
            if (!response.ok) throw new Error('Backup fehlgeschlagen');
            
            const result = await response.json();
            this.sessionId = result.sessionId || this.sessionId;
        } catch (error) {
            console.warn('Server-Backup fehlgeschlagen:', error);
        }
    }

    startAutoSave() {
        this.autoSaveInterval = setInterval(() => {
            this.saveSession();
        }, APP_CONFIG.AUTO_SAVE_INTERVAL);
    }

    stopAutoSave() {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
            this.autoSaveInterval = null;
        }
    }
}

// API-Service für Backend-Kommunikation
class ApiService {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API-Fehler bei ${endpoint}:`, error);
            throw error;
        }
    }

    // Fahrzeug-Suche (HSN/TSN)
    async searchVehicle(hsn, tsn) {
        return this.request(`/vehicles.php?hsn=${hsn}&tsn=${tsn}`);
    }

    // Diagnose starten
    async startDiagnosis(vehicleId, problemDescription) {
        return this.request('/diagnose.php', {
            method: 'POST',
            body: JSON.stringify({
                vehicleId,
                problemDescription,
                lang: APP_CONFIG.LOCALE
            })
        });
    }

    // Nächste Frage beantworten
    async answerQuestion(sessionId, questionId, answer) {
        return this.request('/diagnose.php', {
            method: 'POST',
            body: JSON.stringify({
                sessionId,
                questionId,
                answer,
                lang: APP_CONFIG.LOCALE
            })
        });
    }

    // Werkstattsuche
    async searchWorkshops(lat, lng, radius = 15) {
        return this.request(`/workshops.php?lat=${lat}&lng=${lng}&radius=${radius}`);
    }

    // Teilepreise abrufen
    async getPartPrices(parts) {
        return this.request('/parts.php', {
            method: 'POST',
            body: JSON.stringify({ parts })
        });
    }
}

// UI-Komponenten
class UIManager {
    constructor() {
        this.loadingStates = new Map();
    }

    showLoading(id, message = 'Laden...') {
        const element = document.getElementById(id);
        if (!element) return;

        const loader = this.createLoader(message);
        element.innerHTML = '';
        element.appendChild(loader);
        
        // Füge Skelett-Screen hinzu, wenn es Inhalt gibt
        this.showSkeleton(element);
    }

    hideLoading(id) {
        const element = document.getElementById(id);
        if (element) {
            element.querySelector('.loader')?.remove();
            element.querySelectorAll('.skeleton').forEach(s => s.remove());
        }
    }

    createLoader(message) {
        const loader = document.createElement('div');
        loader.className = 'loader-container';
        loader.innerHTML = `
            <div class="loader" aria-label="${message}">
                <div class="loader-spinner"></div>
                <div class="loader-text">${message}</div>
            </div>
        `;
        return loader;
    }

    showSkeleton(element) {
        // Füge Blur-Effekt hinzu
        element.classList.add('blur-sm');
        
        // Entferne Blur nach kurzer Zeit für smooth transition
        setTimeout(() => {
            element.classList.remove('blur-sm');
        }, 300);
    }

    showSuccess(message, duration = 3000) {
        this.showToast(message, 'success', duration);
    }

    showError(message, duration = 5000) {
        this.showToast(message, 'error', duration);
    }

    showWarning(message, duration = 4000) {
        this.showToast(message, 'warning', duration);
    }

    showToast(message, type, duration) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${this.getIconForType(type)}</span>
                <span class="toast-message">${message}</span>
            </div>
            <button class="toast-close" aria-label="Schließen">×</button>
        `;

        const container = document.getElementById('toast-container') || this.createToastContainer();
        container.appendChild(toast);

        // Animation entfernen
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Autschließen
        setTimeout(() => {
            toast.remove();
        }, duration);

        // Manuelles Schließen
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    getIconForType(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠'
        };
        return icons[type] || 'ℹ';
    }

    // Navigation zwischen Schritten
    navigateToStep(step) {
        const steps = document.querySelectorAll('.step');
        steps.forEach(s => s.classList.add('hidden'));
        
        const activeStep = document.querySelector(`[data-step="${step}"]`);
        if (activeStep) {
            activeStep.classList.remove('hidden');
            
            // Scroll-Animation
            activeStep.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Mikro-Animation
            setTimeout(() => {
                activeStep.style.transform = 'translateY(0)';
                activeStep.style.opacity = '1';
            }, 100);
        }
    }

    // Responsive Mobile Navigation
    toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('open');
    }
}

// Diagnose-Engine
class DiagnosisEngine {
    constructor(apiService, uiManager, appState) {
        this.api = apiService;
        this.ui = uiManager;
        this.state = appState;
        this.currentSessionData = null;
        this.questions = [];
    }

    async start(vehicleId, problemDescription) {
        try {
            this.ui.showLoading('diagnosis-content', 'Diagnose wird erstellt...');
            
            const result = await this.api.startDiagnosis(vehicleId, problemDescription);
            
            this.currentSessionData = result;
            this.state.sessionId = result.sessionId;
            this.state.diagnosisSession = result;
            
            this.renderQuestions();
            
        } catch (error) {
            this.ui.showError('Diagnose konnte nicht gestartet werden. Bitte versuchen Sie es erneut.');
            console.error(error);
        } finally {
            this.ui.hideLoading('diagnosis-content');
        }
    }

    async answerQuestion(questionId, answer) {
        try {
            this.ui.showLoading('question-container', 'Verarbeite Antwort...');
            
            const result = await this.api.answerQuestion(
                this.state.sessionId, 
                questionId, 
                answer
            );
            
            this.currentSessionData = result;
            
            if (result.isComplete) {
                this.showResults(result);
            } else {
                this.renderQuestions();
            }
            
        } catch (error) {
            this.ui.showError('Antwort konnte nicht verarbeitet werden.');
            console.error(error);
        } finally {
            this.ui.hideLoading('question-container');
        }
    }

    renderQuestions() {
        const container = document.getElementById('question-container');
        if (!container || !this.currentSessionData) return;

        const { currentQuestion, questions } = this.currentSessionData;
        
        // Verhindere Wiederholungen derselben Frage
        if (questions.length && questions[questions.length - 1].id === currentQuestion.id) {
            return;
        }

        container.innerHTML = `
            <div class="question-card">
                <div class="question-header">
                    <h3>${currentQuestion.title}</h3>
                    <p class="question-hint">${currentQuestion.hint || ''}</p>
                </div>
                
                <div class="question-content">
                    ${this.renderQuestionInput(currentQuestion)}
                </div>
                
                <div class="question-footer">
                    <span class="question-number">
                        Frage ${questions.length + 1}
                    </span>
                    <button class="btn-help" onclick="diagnosis.showHelp('${currentQuestion.id}')">
                        Ich weiß nicht weiter
                    </button>
                </div>
            </div>
        `;
    }

    renderQuestionInput(question) {
        switch (question.type) {
            case 'boolean':
                return `
                    <div class="answer-buttons">
                        <button class="btn-answer" data-value="yes">
                            Ja ${question.positiveText || ''}
                        </button>
                        <button class="btn-answer" data-value="no">
                            Nein ${question.negativeText || ''}
                        </button>
                    </div>
                `;
                
            case 'multiple':
                return `
                    <div class="answer-options">
                        ${question.options.map(option => `
                            <label class="answer-option">
                                <input type="radio" name="question" value="${option.value}">
                                <span>${option.text}</span>
                            </label>
                        `).join('')}
                    </div>
                    <button class="btn-primary" onclick="diagnosis.submitMultiple()">
                        Weiter
                    </button>
                `;
                
            case 'text':
                return `
                    <div class="answer-text">
                        <input type="text" id="text-answer" placeholder="${question.placeholder || 'Ihre Antwort...'}">
                        <button class="btn-primary" onclick="diagnosis.submitText()">
                            Weiter
                        </button>
                    </div>
                `;
                
            case 'image':
                return `
                    <div class="answer-image">
                        <input type="file" id="image-input" accept="image/*" multiple>
                        <button class="btn-primary" onclick="diagnosis.submitImage()">
                            Bilder hochladen
                        </button>
                    </div>
                `;
                
            default:
                return '<p>Unbekannter Fragetyp</p>';
        }
    }

    showResults(result) {
        const container = document.getElementById('diagnosis-content');
        
        container.innerHTML = `
            <div class="results-container">
                <div class="results-header">
                    <h2>Diagnose abgeschlossen</h2>
                    ${this.renderConfidenceIndicator(result.certainty)}
                </div>
                
                <div class="results-main">
                    ${this.renderDiagnosisDetail(result)}
                    ${this.renderSolutionOptions(result.solutions)}
                </div>
                
                <div class="results-footer">
                    <button class="btn-secondary" onclick="diagnosis.startFromNew()">
                        Neue Diagnose
                    </button>
                    <button class="btn-primary" onclick="diagnosis.saveResults()">
                        Ergebnisse speichern
                    </button>
                </div>
            </div>
        `;
        
        // Animation der Ergebnisse
        this.animateResults();
    }

    renderConfidenceIndicator(certainty) {
        const color = certainty > 0.8 ? '#22c55e' : certainty > 0.5 ? '#f59e0b' : '#ef4444';
        
        return `
            <div class="confidence-indicator" title="Genauigkeit: ${Math.round(certainty * 100)}%">
                <div class="confidence-bar">
                    <div class="confidence-fill" 
                         style="width: ${certainty * 100}%; background-color: ${color}"></div>
                </div>
                <span class="confidence-text">
                    ${Math.round(certainty * 100)}% Sicherheit
                </span>
            </div>
        `;
    }

    renderSolutionOptions(solutions) {
        return solutions.map((solution, index) => `
            <div class="solution-card">
                <h3>${solution.type === 'diy' ? 'Selbst reparieren' : 
                           solution.type === 'hybrid' ? 'Teil reparieren + Werkstatt' : 
                           'Zur Werkstatt'}</h3>
                
                <div class="solution-cost">
                    <span class="cost-label">Geschätzte Kosten:</span>
                    <span class="cost-amount">${solution.costRange}</span>
                </div>
                
                <div class="solution-steps">
                    <h4>Schritte:</h4>
                    <ol>${solution.steps.map(step => `<li>${step}</li>`).join('')}</ol>
                </div>
                
                <div class="solution-materials">
                    ${solution.materials ? `<h4>Benötigte Teile:</h4>
                    <ul>${solution.materials.map(m => `<li>${m.name} - ${m.estimatedPrice}</li>`).join('')}</ul>` : ''}
                </div>
                
                <button class="btn-primary" onclick="workshopFinder.showWorkshops('${JSON.stringify(solution.workshopTerms).replace(/"/g, '&quot;')}')">
                    ${solution.type === 'workshop' ? 'Werkstätten finden' : 'Anleitung anzeigen'}
                </button>
            </div>
        `).join('');
    }

    animateResults() {
        const cards = document.querySelectorAll('.solution-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transform = 'translateY(0)';
                card.style.opacity = '1';
            }, index * 200);
        });
    }

    showHelp(questionId) {
        // Zeige Spezial-Hilfetext für diese Frage
        this.ui.showToast(
            'Hilfe ist unterwegs! So ermitteln Sie die richtige Antwort: [spezifische Anleitung]',
            'warning',
            8000
        );
    }

    async saveResults() {
        try {
            await this.api.request('/session.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'save',
                    sessionId: this.state.sessionId,
                    diagnosis: this.currentSessionData
                })
            });
            
            this.ui.showSuccess('Diagnose wurde erfolgreich gespeichert!');
            
            // Download als PDF
            this.downloadAsPDF();
            
        } catch (error) {
            this.ui.showError('Speichern fehlgeschlagen');
        }
    }

    downloadAsPDF() {
        // TODO: Implementiere PDF-Download
        console.log('PDF-Download würde hier stattfinden');
    }

    startFromNew() {
        this.state.clearSession();
        this.currentSessionData = null;
        window.location.reload();
    }
}

// Werkstatt-Finder Service
class WorkshopFinder {
    constructor(apiService, uiManager) {
        this.api = apiService;
        this.ui = uiManager;
        this.currentLocation = null;
        this.workshops = [];
    }

    async getLocation() {
        return new Promise((resolve, reject) => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => resolve(position.coords),
                    error => reject(error),
                    { timeout: 10000 }
                );
            } else {
                reject(new Error('Geolocation wird nicht unterstützt'));
            }
        });
    }

    async showWorkshops(searchTerms) {
        try {
            this.ui.showLoading('workshop-container', 'Werkstätten werden gesucht...');
            
            this.currentLocation = await this.getLocation();
            
            const workshops = await this.api.searchWorkshops(
                this.currentLocation.latitude,
                this.currentLocation.longitude
            );
            
            this.renderWorkshops(workshops, searchTerms);
            
        } catch (error) {
            this.ui.showError('Werkstattsuche fehlgeschlagen. Bitte geben Sie manuell Ihre Postleitzahl ein.');
            this.showManualInput();
        } finally {
            this.ui.hideLoading('workshop-container');
        }
    }

    renderWorkshops(workshops, searchTerms) {
        const container = document.getElementById('workshop-container');
        
        container.innerHTML = `
            <div class="workshop-header">
                <h3>Gefundene Werkstätten</h3>
                <p>Sie suchen nach: ${searchTerms.join(', ')}</p>
            </div>
            
            <div class="workshop-list">
                ${workshops.map((workshop, index) => this.renderWorkshopCard(workshop, index)).join('')}
            </div>
        `;
        
        // Füge Klick-Handler hinzu
        this.addWorkshopHandlers();
    }

    renderWorkshopCard(workshop, index) {
        const rating = workshop.rating || 0;
        
        return `
            <div class="workshop-card" data-workshop-id="${workshop.place_id}">
                <div class="workshop-info">
                    <h4>${workshop.name}</h4>
                    <div class="workshop-rating">
                        ${this.renderStars(rating)}
                        <span>${workshop.rating} (${workshop.ratingCount})</span>
                    </div>
                    <p class="workshop-address">${workshop.formatted_address}</p>
                    <p class="workshop-phone">${workshop.formatted_phone_number || ''}</p>
                </div>
                
                <div class="workshop-actions">
                    <span class="workshop-distance">${workshop.distance} km entfernt</span>
                    <button class="btn-primary" onclick="workshopFinder.bookWorkshop('${workshop.place_id}')">
                        Buchen
                    </button>
                </div>
            </div>
        `;
    }

    renderStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 !== 0;
        let stars = '';
        
        for (let i = 0; i < fullStars; i++) {
            stars += '⭐';
        }
        
        if (hasHalfStar) {
            stars += '⭐'; // TODO: Halbe Sterne rendern
        }
        
        return stars;
    }

    showManualInput() {
        const container = document.getElementById('workshop-container');
        
        container.innerHTML = `
            <div class="manual-search">
                <h3>PLZ eingeben</h3>
                <div class="input-group">
                    <input type="text" id="zip-code" placeholder="Postleitzahl">
                    <button class="btn-primary" onclick="workshopFinder.searchByZip()">
                        Suchen
                    </button>
                </div>
            </div>
        `;
    }

    addWorkshopHandlers() {
        const cards = document.querySelectorAll('.workshop-card');
        cards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.classList.contains('btn-primary')) {
                    e.currentTarget.classList.toggle('expanded');
                }
            });
        });
    }
}

// Initialisierung der App
let appState, apiService, uiManager, diagnosis, workshopFinder;

document.addEventListener('DOMContentLoaded', () => {
    // Basisklassen initialisieren
    appState = new AppState();
    apiService = new ApiService(APP_CONFIG.API_BASE_URL);
    uiManager = new UIManager();
    diagnosis = new DiagnosisEngine(apiService, uiManager, appState);
    workshopFinder = new WorkshopFinder(apiService, uiManager);

    // Event-Listener registrieren
    initEventListeners();
    
    // PWA-Funktionalität
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
});

function initEventListeners() {
    // Auto-Suche HSN/TSN
    document.getElementById('hsn-input')?.addEventListener('input', debounce(searchVehicle, 500));
    document.getElementById('tsn-input')?.addEventListener('input', debounce(searchVehicle, 500));
    
    // Problem-Beschreibung
    document.getElementById('problem-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const description = document.getElementById('problem-description').value;
        diagnosis.start(appState.vehicleInfo.id, description);
    });
    
    // Mobile Navigation
    document.getElementById('mobile-menu-toggle')?.addEventListener('click', () => {
        uiManager.toggleMobileMenu();
    });
    
    // Theme-Toggle
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
}

// Utility-Funktionen
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

async function searchVehicle() {
    const hsn = document.getElementById('hsn-input').value?.toUpperCase();
    const tsn = document.getElementById('tsn-input').value?.toUpperCase();
    
    if (!hsn || !tsn || hsn.length < 4 || tsn.length < 3) return;
    
    try {
        uiManager.showLoading('vehicle-results', 'Suche Fahrzeug...');
        
        const vehicles = await apiService.searchVehicle(hsn, tsn);
        
        if (vehicles.length === 0) {
            uiManager.showError('Kein Fahrzeug gefunden. Bitte überprüfen Sie HSN/TSN.');
            return;
        }
        
        // Zeige Fahrzeug-Auswahl
        renderVehicleSelection(vehicles);
        
    } catch (error) {
        uiManager.showError('Fahrzeugsuche fehlgeschlagen.');
    } finally {
        uiManager.hideLoading('vehicle-results');
    }
}

function renderVehicleSelection(vehicles) {
    const container = document.getElementById('vehicle-results');
    
    container.innerHTML = `
        <div class="vehicle-selection">
            <h3>Fahrzeug auswählen</h3>
            <div class="vehicle-list">
                ${vehicles.map(vehicle => `
                    <div class="vehicle-card" onclick="selectVehicle(${vehicle.id})">
                        <h4>${vehicle.make} ${vehicle.model} ${vehicle.variant}</h4>
                        <p>${vehicle.engine} - ${vehicle.power_kw}kW (${vehicle.power_ps}PS)</p>
                        <p>${vehicle.fuel_type} - ${vehicle.year_from}-${vehicle.year_to}</p>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function selectVehicle(vehicleId) {
    // Übergang zur Diagnose
    appState.vehicleInfo.id = vehicleId;
    appState.currentStep = 2;
    appState.updateProgressBar();
    uiManager.navigateToStep(2);
    
    // Smooth-Scroll zum Problem-Formular
    document.getElementById('problem-form').scrollIntoView({ behavior: 'smooth' });
}

// Beim Verlassen der Seite
window.addEventListener('beforeunload', () => {
    appState.saveSession();
    appState.stopAutoSave();
});

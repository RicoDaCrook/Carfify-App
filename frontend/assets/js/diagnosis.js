/**
 * Carfify Diagnosis Engine
 * Core JavaScript Module für Fahrzeug-Diagnose-Workflow
 * ----------------------------------------------
 * Dieses Modul handhabt:
 * - Fahrzeugidentifikation (HSN/TSN)
 * - Step-by-Step Diagnose
 * - KI-Integration mit Claude
 * - Real-time Preisanfragen
 * - Werkstattsuche
 * ----------------------------------------------
 */

// =========================================================
// GLOBAL CONFIG
// =========================================================
const CONFIG = {
    API_BASE: '/backend/api/',
    CLAUDE_MODEL: 'claude-3-sonnet-20240229',
    GEMINI_MODEL: 'gemini-1.5-flash',
    CACHE_DURATION: 3600000, // 1 Stunde in ms
    MAX_RETRIES: 3,
    SESSION_KEY: 'carfify_diagnosis_session'
};

// =========================================================
// DIANOSIS STATE MANAGER
// =========================================================
class DiagnosisState {
    constructor() {
        this.currentSession = this.loadSession();
        this.currentStep = 0;
        this.vehicle = null;
        this.problem = '';
        this.questions = [];
        this.answers = {};
        this.diagnosis = null;
        this.workshops = [];
        this.solutions = [];
    }

    // Persistenz im Session Storage
    saveSession() {
        const session = {
            id: this.currentSession?.id || Date.now().toString(),
            vehicle: this.vehicle,
            problem: this.problem,
            questions: this.questions,
            answers: this.answers,
            diagnosis: this.diagnosis,
            currentStep: this.currentStep,
            timestamp: Date.now()
        };
        
        sessionStorage.setItem(CONFIG.SESSION_KEY, JSON.stringify(session));
        this.currentSession = session;
    }

    loadSession() {
        const saved = sessionStorage.getItem(CONFIG.SESSION_KEY);
        return saved ? JSON.parse(saved) : null;
    }

    clearSession() {
        sessionStorage.removeItem(CONFIG.SESSION_KEY);
        this.reset();
    }

    reset() {
        this.currentSession = null;
        this.currentStep = 0;
        this.vehicle = null;
        this.problem = '';
        this.questions = [];
        this.answers = {};
        this.diagnosis = null;
        this.workshops = [];
        this.solutions = [];
    }
}

// =========================================================
// VEHICLE IDENTIFICATION MODULE
// =========================================================
class VehicleIdentifier {
    constructor() {
        this.cache = new Map();
    }

    async searchByHSNTSN(hsn, tsn) {
        const cacheKey = `hsn-${hsn}-${tsn}`;
        
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await fetch(`${CONFIG.API_BASE}vehicles.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'search_by_hsn_tsn',
                    hsn: hsn,
                    tsn: tsn
                })
            });

            if (!response.ok) {
                throw new Error('Fahrzeug suche fehlgeschlagen');
            }

            const data = await response.json();
            
            if (data.success && data.vehicle) {
                this.cache.set(cacheKey, data.vehicle, CONFIG.CACHE_DURATION);
                return data.vehicle;
            }

            throw new Error(data.error || 'Fahrzeug nicht gefunden');

        } catch (error) {
            console.error('Vehicle search error:', error);
            throw error;
        }
    }

    async searchByVIN(vin) {
        // VIN-Suche für spätere Erweiterung
        const response = await fetch(`${CONFIG.API_BASE}vehicles.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'search_by_vin',
                vin: vin
            })
        });

        return response.json();
    }
}

// =========================================================
// DIAGNOSIS ENGINE
// =========================================================
class DiagnosisEngine {
    constructor(state) {
        this.state = state;
        this.vehicleAPI = new VehicleIdentifier();
    }

    async startDiagnosis(vehicleId, problemDescription) {
        try {
            // Neue Diagnose-Session starten
            this.state.vehicle = await this.vehicleAPI.searchByHSNTSN(
                vehicleId.hsn,
                vehicleId.tsn
            );

            this.state.problem = problemDescription;
            this.state.currentStep = 1;

            // Erste KI-Frage anfordern
            await this.requestNextQuestion();
            this.state.saveSession();

        } catch (error) {
            throw new Error(`Diagnose-Initialisierung fehlgeschlagen: ${error.message}`);
        }
    }

    async requestNextQuestion() {
        const prompt = this.buildClaudePrompt();
        
        try {
            const response = await fetch(`${CONFIG.API_BASE}diagnose.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'get_next_question',
                    vehicle: this.state.vehicle,
                    problem: this.state.problem,
                    previous_answers: this.state.answers,
                    prompt: prompt
                })
            });

            const data = await response.json();
            
            if (data.next_question) {
                this.state.questions.push(data.next_question);
                this.renderQuestion(data.next_question);
                showStep(2); // Frage anzeigen
            } else if (data.diagnosis) {
                this.state.diagnosis = data.diagnosis;
                this.generateSolutions();
            }

        } catch (error) {
            console.error('Question generation error:', error);
            showError('Bitte versuchen Sie es erneut.');
        }
    }

    buildClaudePrompt() {
        const vehicle = this.state.vehicle;
        const capitalize = str => str.charAt(0).toUpperCase() + str.slice(1);
        
        return {
            role: "Fahrzeugdiagnose-Expertenassistent",
            task: "Automotive diagnosis via conversational questioning",
            output: "Generate ONE specific, easy question about the described problem",
            format: {
                question: '<simple, non-technical question in perfect German>',
                category: 'symptom|sound|visual|behavior|context',
                importance: 'high|medium|low'
            },
            constraints: [
                'Ask like a friendly mechanic, no jargon',
                'One question only',
                'German language',
                'Yes/no or one clear answer'
            ],
            context: {
                vehicle: `${capitalize(vehicle.make)} ${vehicle.model}`,
                engine: `${vehicle.engine} ${vehicle.power_kw}kW`,
                problem: this.state.problem,
                answers_so_far: this.state.answers
            }
        };
    }

    submitAnswer(answer) {
        const currentQuestion = this.state.questions[this.state.questions.length - 1];
        
        // Speichere Antwort
        this.state.answers[currentQuestion.question] = answer;
        this.state.saveSession();
        
        // Nächste Frage oder Diagnose
        if (this.state.questions.length >= 5 || answer === 'unsicher') {
            this.generateFinalDiagnosis();
        } else {
            this.requestNextQuestion();
        }
    }

    async generateFinalDiagnosis() {
        // Führe finale KI-Analyse durch
        const response = await fetch(`${CONFIG.API_BASE}analyze.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                vehicle: this.state.vehicle,
                problem: this.state.problem,
                answers: this.state.answers,
                action: 'generate_diagnosis'
            })
        });

        const data = await response.json();
        
        if (data.diagnosis) {
            this.state.diagnosis = data.diagnosis;
            this.state.currentStep = 3;
            this.state.saveSession();
            
            this.generateSolutions();
            showStep(3);
        }
    }

    async generateSolutions() {
        // Drei Lösungswege generieren
        const solutions = [
            {
                type: 'diy',
                title: 'Einfache Selbstreparatur',
                difficulty: 1,
                duration: '30-90 Minuten',
                savings: '150-300€',
                guide: null
            },
            {
                type: 'hybrid',
                title: 'Do It Together (Parts + Workshop)',
                difficulty: 2,
                duration: '2-3 Stunden Inkl. Werkstatt',
                savings: '90-180€',
                guide: null
            },
            {
                type: 'workshop',
                title: 'Professionelle Werkstatt',
                difficulty: 0,
                duration: '1-3 Stunden',
                savings: '50-100€',
                guide: null
            }
        ];

        // Gezielte Anleitungen basierend auf Diagnose laden
        for (let solution of solutions) {
            try {
                const guide = await this.loadGuideForDiagnosis(
                    solution.type,
                    this.state.diagnosis.primaryIssue
                );
                solution.guide = guide;
            } catch (e) {
                console.error(`Guide loading failed for ${solution.type}:`, e);
            }
        }

        this.state.solutions = solutions;
        renderSolutions(solutions);
    }

    async loadGuideForDiagnosis(type, issue) {
        const response = await fetch(`${CONFIG.API_BASE}guides.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'get_guide',
                issue: issue,
                vehicle: this.state.vehicle,
                solution_type: type
            })
        });

        return response.json();
    }
}

// =========================================================
// WORKSHOP FINDER
// =========================================================
class WorkshopFinder {
    constructor() {
        this.cache = new Map();
    }

    async findWorkshops(filters = {}) {
        const cacheKey = this.buildCacheKey(filters);
        
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await fetch(`${CONFIG.API_BASE}workshops.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'search_workshops',
                    filters: filters,
                    location: await this.getUserLocation()
                })
            });

            const data = await response.json();
            
            if (data.workshops) {
                this.cache.set(cacheKey, data.workshops, CONFIG.CACHE_DURATION);
                return data.workshops;
            }
            
            throw new Error(data.error || 'Keine Werkstätten gefunden');

        } catch (error) {
            console.error('Workshop search error:', error);
            throw error;
        }
    }

    async getUserLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(null); // Kein Location-Zugriff
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    });
                },
                () => resolve(null), // Permission denied
                { timeout: 5000 }
            );
        });
    }

    buildCacheKey(filters) {
        return `workshops-${JSON.stringify(filters).replace(/\s+/g, '')}`;
    }
}

// =========================================================
// UI MANAGEMENT
// =========================================================
class DiagnosisUI {
    constructor(engine) {
        this.engine = engine;
        this.workshopFinder = new WorkshopFinder();
    }

    initEventListeners() {
        // Fahrzeug Suche
        document.getElementById('vehicle-search-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleVehicleSearch();
        });

        // Diagnose-Start
        document.getElementById('start-diag-btn')?.addEventListener('click', () => {
            this.startNewDiagnosis();
        });

        // Antwort-Buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('answer-btn')) {
                this.handleAnswerClick(e.target);
            }
            
            if (e.target.classList.contains('workshop-toggle')) {
                this.toggleWorkshopDetails(e.target.dataset.workshopId);
            }
        });
    }

    async handleVehicleSearch() {
        const hsn = document.getElementById('hsn-input').value.trim();
        const tsn = document.getElementById('tsn-input').value.trim();
        const errorField = document.getElementById('vehicle-error');

        if (!hsn || !tsn) {
            errorField.textContent = 'Bitte HSN und TSN eingeben';
            return;
        }

        try {
            showLoading();
            const vehicle = await this.engine.vehicleAPI.searchByHSNTSN(hsn, tsn);
            
            document.getElementById('vehicle-display').innerHTML = `
                <div class="vehicle-card">
                    <h3>${vehicle.make} ${vehicle.model}</h3>
                    <p>${vehicle.engine} | ${vehicle.power_kw}kW | ${vehicle.year_from}-${vehicle.year_to}</p>
                </div>
            `;
            
            hideLoading();
            showStep(1); // Problem-Beschreibung
        } catch (error) {
            errorField.textContent = `Fahrzeug nicht gefunden: ${error.message}`;
            hideLoading();
        }
    }

    async startNewDiagnosis() {
        const problem = document.getElementById('problem-input').value.trim();
        if (!problem) {
            showError('Bitte beschreiben Sie Ihr Problem');
            return;
        }

        try {
            await this.engine.startDiagnosis(
                this.engine.state.vehicle,
                problem
            );
        } catch (error) {
            showError(error.message);
        }
    }

    handleAnswerClick(button) {
        const answer = button.dataset.answer;
        this.engine.submitAnswer(answer);
        
        // UI Update
        document.querySelectorAll('.answer-btn').forEach(btn => btn.disabled = true);
        button.classList.add('selected');
    }

    async loadWorkshops() {
        const filters = {
            rating: document.getElementById('filter-rating').value,
            specialties: Array.from(document.querySelectorAll('input[name="specialties"]:checked')).map(cb => cb.value),
            price: document.getElementById('filter-price').value
        };

        try {
            const workshops = await this.workshopFinder.findWorkshops(filters);
            this.displayWorkshops(workshops);
        } catch (error) {
            showError('Werkstatt-Suche fehlgeschlagen');
        }
    }

    displayWorkshops(workshops) {
        const container = document.getElementById('workshops-list');
        
        if (workshops.length === 0) {
            container.innerHTML = '<p>Keine Werkstätten gefunden</p>';
            return;
        }

        container.innerHTML = workshops.map(workshop => `
            <div class="workshop-card">
                <h4>${workshop.name}</h4>
                <div class="rating">
                    ${'★'.repeat(Math.floor(workshop.rating))}${'☆'.repeat(5-Math.floor(workshop.rating))}
                    <span>${workshop.review_count} Bewertungen</span>
                </div>
                <p>${workshop.address}</p>
                <p class="price">${workshop.price_range || 'Preis auf Anfrage'}</p>
                <button class="workshop-toggle" data-workshop-id="${workshop.id}">
                    Details & Termin
                </button>
                
                <div id="workshop-${workshop.id}" class="workshop-details hidden">
                    <ul>
                        ${workshop.services.map(s => `<li>${s}</li>`).join('')}
                    </ul>
                    <button onclick="bookAppointment(${workshop.id})">Jetzt terminieren</button>
                </div>
            </div>
        `).join('');
    }

    toggleWorkshopDetails(workshopId) {
        const details = document.getElementById(`workshop-${workshopId}`);
        details.classList.toggle('hidden');
        
        // Smooth scroll
        details.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// =========================================================
// HELPER FUNCTIONS
// =========================================================
function showLoading() {
    document.getElementById('loading-overlay')?.classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-overlay')?.classList.add('hidden');
}

function showStep(stepNumber) {
    document.querySelectorAll('.diagnosis-step').forEach(step => {
        step.classList.add('hidden');
    });
    
    document.getElementById(`step-${stepNumber}`)?.classList.remove('hidden');
    
    // Progress bar
    const progress = (stepNumber / 4) * 100;
    document.getElementById('progress-bar').style.width = `${progress}%`;
}

function showError(message) {
    const errorDiv = document.getElementById('global-error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
        setTimeout(() => errorDiv.classList.add('hidden'), 5000);
    }
}

function renderQuestion(question) {
    const container = document.getElementById('question-container');
    
    container.innerHTML = `
        <div class="question-card">
            <h3>${question.categoryTitle}</h3>
            <p class="question-text">${question.question}</p>
            
            ${question.type === 'boolean' ? `
                <div class="answer-buttons">
                    <button class="answer-btn yes" data-answer="ja">Ja</button>
                    <button class="answer-btn no" data-answer="nein">Nein</button>
                </div>
            ` : `
                <div class="answer-choices">
                    ${question.choices.map(choice => `
                        <button class="answer-btn choice" data-answer="${choice.value}">
                            ${choice.label}
                        </button>
                    `).join('')}
                </div>
            `}
        </div>
    `;
}

function renderSolutions(solutions) {
    const container = document.getElementById('solutions-list');
    
    container.innerHTML = solutions.map(solution => `
        <div class="solution-card ${solution.type}">
            <div class="solution-header">
                <h3>${solution.title}</h3>
                <div class="difficulty-badge ${solution.difficulty}">
                    ${'💰'.repeat(solution.difficulty)}
                </div>
            </div>
            
            <div class="solution-meta">
                <span class="duration">${solution.duration}</span>
                <span class="savings">${solution.savings} sparen</span>
            </div>
            
            ${solution.guide ? `
                <div class="guide-container">
                    <h4>Schritt-für-Schritt Anleitung</h4>
                    ${solution.guide.steps.map((step, i) => `
                        <div class="guide-step">
                            <span class="step-number">${i + 1}</span>
                            <p>${step}</p>
                        </div>
                    `).join('')}
                    
                    <button class="show-video" data-guideid="${solution.guide.id}">
                        Video-Anleitung ansehen
                    </button>
                </div>
            ` : '<p>Anleitung wird geladen...</p>'}
            
            ${solution.type === 'workshop' ? `
                <button onclick="loadWorkshops()" class="find-workshops-btn">
                    Werkstatt finden
                </button>
            ` : ''}
        </div>
    `).join('');
}

// =========================================================
// INITIALIZATION
// =========================================================
document.addEventListener('DOMContentLoaded', () => {
    const state = new DiagnosisState();
    const engine = new DiagnosisEngine(state);
    const ui = new DiagnosisUI(engine);
    
    // Event listeners registrieren
    ui.initEventListeners();
    
    // Session wiederherstellen wenn vorhanden
    if (state.currentSession) {
        if (state.currentSession.diagnosis) {
            state.diagnosis = state.currentSession.diagnosis;
            renderSolutions(state.currentSession.solutions);
            showStep(3);
        } else if (state.currentSession.vehicle) {
            state.vehicle = state.currentSession.vehicle;
            state.problem = state.currentSession.problem;
            showStep(2);
        }
    }
    
    // Globales Diagnosis State
    window.diagnosisState = state;
    window.diagnosisEngine = engine;
});

// =========================================================
// GLOBAL FUNCTIONS (für inline onclick)
// =========================================================
window.bookAppointment = async (workshopId) => {
    const response = await fetch('/backend/api/appointments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'book_appointment',
            workshop_id: workshopId,
            diagnosis: window.diagnosisState.diagnosis
        })
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert(`Termin vereinbart! ID: ${result.appointment_id}`);
    } else {
        alert('Termin-Buchung fehlgeschlagen: ' + result.error);
    }
}

// =========================================================
// PERFORMANCE MONITORING
// =========================================================
if (window.performance && window.performance.mark) {
    // Mark launch
    performance.mark('diagnosis-module-ready');
    
    // Measure load time
    window.addEventListener('load', () => {
        performance.mark('diagnosis-ui-ready');
        performance.measure('diagnosis-initialization', 
            'diagnosis-module-ready', 
            'diagnosis-ui-ready');
    });
}

// =========================================================
// SERVICE WORKER REGISTRATION (Optional PWA Support)
// =========================================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(err => 
            console.log('SW registration failed: ', err));
    });
}

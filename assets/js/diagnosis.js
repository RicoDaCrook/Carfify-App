class DiagnosisSystem {
    constructor() {
        this.currentSymptom = null;
        this.answers = {};
        this.checkedItems = new Set();
        this.location = null;
        this.hsn = '';
        this.tsn = '';
        
        this.init();
    }
    
    init() {
        this.setupLocationModal();
        this.setupHsnTsnInput();
        this.setupChat();
        this.setupTabs();
    }
    
    setupLocationModal() {
        const allowBtn = document.getElementById('allow-location');
        const skipBtn = document.getElementById('skip-location');
        
        allowBtn.addEventListener('click', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.location = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        this.closeLocationModal();
                    },
                    () => {
                        this.closeLocationModal();
                    }
                );
            } else {
                this.closeLocationModal();
            }
        });
        
        skipBtn.addEventListener('click', () => {
            this.closeLocationModal();
        });
    }
    
    closeLocationModal() {
        document.getElementById('location-modal').classList.remove('active');
        document.getElementById('hsn-tsn-section').classList.remove('hidden');
    }
    
    setupHsnTsnInput() {
        const startBtn = document.getElementById('start-diagnosis');
        
        startBtn.addEventListener('click', () => {
            this.hsn = document.getElementById('hsn').value.trim();
            this.tsn = document.getElementById('tsn').value.trim();
            
            if (this.hsn && this.tsn) {
                this.startDiagnosis();
            } else {
                alert('Bitte geben Sie HSN und TSN ein.');
            }
        });
    }
    
    async startDiagnosis() {
        document.getElementById('hsn-tsn-section').classList.add('hidden');
        document.getElementById('diagnosis-layout').classList.remove('hidden');
        
        await this.loadQuickQuestions();
        this.setupQuestionHandlers();
    }
    
    async loadQuickQuestions() {
        try {
            const response = await fetch('/api/diagnose.php?action=quick-questions');
            const data = await response.json();
            
            const container = document.getElementById('quick-questions');
            container.innerHTML = '';
            
            data.questions.forEach(q => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'question-item';
                questionDiv.innerHTML = `
                    <h4>${q.question}</h4>
                    <p class="question-description">${q.description}</p>
                    <div class="answer-buttons">
                        <button class="btn-answer" data-question="${q.id}" data-answer="yes">Ja</button>
                        <button class="btn-answer" data-question="${q.id}" data-answer="no">Nein</button>
                        <button class="btn-answer" data-question="${q.id}" data-answer="unknown">Weiß nicht</button>
                    </div>
                `;
                container.appendChild(questionDiv);
            });
        } catch (error) {
            console.error('Fehler beim Laden der Fragen:', error);
        }
    }
    
    setupQuestionHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-answer')) {
                const question = e.target.dataset.question;
                const answer = e.target.dataset.answer;
                
                this.answers[question] = answer;
                this.updateAnswerButtons(question, answer);
                this.updateSafetyScore();
                
                if (answer === 'yes') {
                    this.loadChecklist(question);
                }
            }
        });
    }
    
    updateAnswerButtons(question, selectedAnswer) {
        const buttons = document.querySelectorAll(`[data-question="${question}"]`);
        buttons.forEach(btn => {
            btn.classList.toggle('selected', btn.dataset.answer === selectedAnswer);
        });
    }
    
    async updateSafetyScore() {
        try {
            const params = new URLSearchParams();
            Object.entries(this.answers).forEach(([q, a]) => {
                params.append('answers[]', `${q}:${a}`);
            });
            
            const response = await fetch(`/api/diagnose.php?action=safety-score&${params}`);
            const data = await response.json();
            
            const score = data.safety_score;
            document.getElementById('safety-percentage').textContent = `${score}%`;
            
            const fill = document.getElementById('safety-fill');
            fill.style.width = `${score}%`;
            
            if (score < 60) {
                fill.style.backgroundColor = '#ff4444';
            } else if (score < 80) {
                fill.style.backgroundColor = '#ffaa00';
            } else {
                fill.style.backgroundColor = '#00aa00';
            }
        } catch (error) {
            console.error('Fehler beim Berechnen des Sicherheitsscores:', error);
        }
    }
    
    async loadChecklist(symptom) {
        try {
            const response = await fetch(`/api/diagnose.php?action=checklist&symptom=${symptom}`);
            const data = await response.json();
            
            const container = document.getElementById('checklist');
            container.innerHTML = '';
            
            data.checklist.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'checklist-item';
                itemDiv.innerHTML = `
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="${item.id}" data-item="${item.id}">
                        <label for="${item.id}">
                            <span class="checkmark"></span>
                            <div class="item-content">
                                <h4>${item.title}</h4>
                                <p class="instruction">${item.instruction}</p>
                                <div class="item-meta">
                                    <span class="difficulty ${item.difficulty}">${item.difficulty}</span>
                                    <span class="time">⏱️ ${item.time}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                `;
                container.appendChild(itemDiv);
                
                const checkbox = itemDiv.querySelector('input[type="checkbox"]');
                checkbox.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        this.checkedItems.add(e.target.dataset.item);
                    } else {
                        this.checkedItems.delete(e.target.dataset.item);
                    }
                    this.updateSolutions();
                });
            });
            
            this.currentSymptom = symptom;
            this.updateSolutions();
        } catch (error) {
            console.error('Fehler beim Laden der Checkliste:', error);
        }
    }
    
    setupChat() {
        const input = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-message');
        
        const sendMessage = async () => {
            const message = input.value.trim();
            if (!message) return;
            
            this.addChatMessage('user', message);
            input.value = '';
            
            try {
                const response = await fetch('/api/diagnose.php?action=chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message=${encodeURIComponent(message)}&context=${encodeURIComponent(JSON.stringify({
                        hsn: this.hsn,
                        tsn: this.tsn,
                        symptom: this.currentSymptom,
                        answers: this.answers
                    }))}`
                });
                
                const data = await response.json();
                this.addChatMessage('meister', data.response);
            } catch (error) {
                this.addChatMessage('meister', 'Entschuldigung, da ist etwas schiefgegangen. Versuchen Sie es bitte erneut.');
            }
        };
        
        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }
    
    addChatMessage(sender, text) {
        const container = document.getElementById('chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        if (sender === 'meister') {
            messageDiv.innerHTML = `
                <div class="avatar">👨‍🔧</div>
                <div class="content">
                    <strong>Meister Müller:</strong><br>
                    ${text}
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="content">
                    ${text}
                </div>
            `;
        }
        
        container.appendChild(messageDiv);
        container.scrollTop = container.scrollHeight;
    }
    
    setupTabs() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.showTabContent(btn.dataset.tab);
            });
        });
    }
    
    updateSolutions() {
        if (!this.currentSymptom) return;
        
        document.getElementById('solution-tabs').classList.remove('hidden');
        this.showTabContent('self');
    }
    
    showTabContent(tab) {
        const content = document.getElementById('tab-content');
        
        const solutions = {
            self: this.generateSelfSolution(),
            hybrid: this.generateHybridSolution(),
            workshop: this.generateWorkshopSolution()
        };
        
        content.innerHTML = solutions[tab] || '';
    }
    
    generateSelfSolution() {
        const checkedCount = this.checkedItems.size;
        const totalCount = document.querySelectorAll('.checklist-item').length;
        const progress = totalCount > 0 ? Math.round((checkedCount / totalCount) * 100) : 0;
        
        return `
            <div class="solution-card">
                <h3>🔧 Selbst reparieren</h3>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${progress}%"></div>
                    <span>${progress}% abgeschlossen</span>
                </div>
                <div class="cost-saving">
                    <strong>💰 Ersparnis: 150-300€</strong>
                    <p>Durch Selbst-Reparatur sparen Sie Arbeitskosten!</p>
                </div>
                <div class="next-steps">
                    <h4>Nächste Schritte:</h4>
                    <ul>
                        <li>✅ Weitere Punkte der Checkliste abarbeiten</li>
                        <li>🛒 Ersatzteile online bestellen</li>
                        <li>📺 YouTube-Tutorials anschauen</li>
                    </ul>
                </div>
            </div>
        `;
    }
    
    generateHybridSolution() {
        return `
            <div class="solution-card">
                <h3>🤝 Hybrid-Lösung</h3>
                <div class="hybrid-options">
                    <div class="option">
                        <h4>Teil 1: Selbst Diagnose</h4>
                        <p>Sie prüfen das Problem genau</p>
                        <span class="price">0€</span>
                    </div>
                    <div class="option">
                        <h4>Teil 2: Werkstatt für Komplexes</h4>
                        <p>Schwierige Arbeit von Profis</p>
                        <span class="price">80-150€</span>
                    </div>
                </div>
                <div class="total-saving">
                    <strong>Gesamt: 80-150€ (statt 200-400€)</strong>
                </div>
            </div>
        `;
    }
    
    generateWorkshopSolution() {
        return `
            <div class="solution-card">
                <h3>🏭 Werkstatt-Empfehlung</h3>
                <div class="workshop-info">
                    <h4>Was die Werkstatt macht:</h4>
                    <ul>
                        <li>🔍 Professionelle Diagnose</li>
                        <li>🛠️ Fachgerechte Reparatur</li>
                        <li>📄 Rechnung mit Gewährleistung</li>
                    </ul>
                </div>
                <div class="price-range">
                    <strong>Kosten: 200-400€</strong>
                    <p>Inklusive Ersatzteile und Arbeitszeit</p>
                </div>
                ${this.location ? `
                    <div class="nearby-workshops">
                        <h4>Werkstätten in Ihrer Nähe:</h4>
                        <p>📍 Klicken Sie hier für Werkstätten in Ihrer Umgebung</p>
                    </div>
                ` : ''}
            </div>
        `;
    }
}

// Initialisierung
document.addEventListener('DOMContentLoaded', () => {
    new DiagnosisSystem();
});
// Diagnose-Flow Controller v2.0
class DiagnoseFlow {
    constructor() {
        this.currentVehicle = null;
        this.diagnosisResult = null;
    }

    startDiagnosis(vehicleData) {
        this.currentVehicle = vehicleData;
        this.showLoadingAnimation();
        
        // Simulierte KI-Analyse (2-3 Sekunden)
        setTimeout(() => {
            this.generateDiagnosis();
            this.showResults();
        }, 2500 + Math.random() * 1000);
    }

    showLoadingAnimation() {
        const modal = document.createElement('div');
        modal.className = 'diagnose-modal';
        modal.innerHTML = `
            <div class="loading-container">
                <div class="ai-animation">
                    <div class="brain-pulse"></div>
                    <div class="data-streams">
                        <span></span><span></span><span></span>
                    </div>
                </div>
                <h3>KI analysiert dein Fahrzeug...</h3>
                <div class="loading-text">
                    <span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Progress-Animation
        const progress = modal.querySelector('.progress-fill');
        let width = 0;
        const interval = setInterval(() => {
            width += Math.random() * 15;
            if (width >= 100) {
                width = 100;
                clearInterval(interval);
            }
            progress.style.width = width + '%';
        }, 100);
    }

    generateDiagnosis() {
        // Simulierte KI-Ergebnisse basierend auf Fahrzeug und Problem
        const confidence = 60 + Math.floor(Math.random() * 40); // 60-100%
        const issues = this.getPossibleIssues();
        
        this.diagnosisResult = {
            confidence: confidence,
            color: this.getConfidenceColor(confidence),
            issues: issues,
            recommendations: this.getRecommendations()
        };
    }

    getPossibleIssues() {
        return [
            { name: "Zündspule defekt", probability: 85, cost: "120-180€" },
            { name: "Kraftstoffpumpe", probability: 65, cost: "300-500€" },
            { name: "Zündkerzen verschmutzt", probability: 45, cost: "50-100€" },
            { name: "Sensorenfehler", probability: 30, cost: "80-150€" }
        ].sort((a, b) => b.probability - a.probability);
    }

    getConfidenceColor(confidence) {
        if (confidence < 50) return { bg: 'linear-gradient(135deg, #ff6b6b, #ff4757)', text: '#fff' };
        if (confidence < 80) return { bg: 'linear-gradient(135deg, #ffa502, #ff6348)', text: '#fff' };
        return { bg: 'linear-gradient(135deg, #2ed573, #1e90ff)', text: '#fff' };
    }

    showResults() {
        const modal = document.querySelector('.diagnose-modal');
        modal.innerHTML = this.createResultsHTML();
        this.animateResults();
    }

    createResultsHTML() {
        const { confidence, color, issues } = this.diagnosisResult;
        
        return `
            <div class="results-container">
                <div class="result-header">
                    <h2>Diagnose-Ergebnis</h2>
                    <button class="close-btn" onclick="this.closest('.diagnose-modal').remove()">×</button>
                </div>
                
                <div class="confidence-card" style="background: ${color.bg}; color: ${color.text}">
                    <div class="confidence-circle">
                        <span class="percentage">${confidence}%</span>
                        <span class="label">Sicherheit</span>
                    </div>
                    <div class="confidence-text">
                        <h3>${confidence > 80 ? 'Sehr wahrscheinlich' : confidence > 50 ? 'Wahrscheinlich' : 'Unsicher'}</h3>
                        <p>Basierend auf ${this.currentVehicle.brand} ${this.currentVehicle.model} Analyse</p>
                    </div>
                </div>

                <div class="issues-list">
                    <h3>Mögliche Ursachen:</h3>
                    ${issues.map(issue => `
                        <div class="issue-item" data-probability="${issue.probability}">
                            <div class="issue-info">
                                <span class="issue-name">${issue.name}</span>
                                <span class="issue-cost">${issue.cost}</span>
                            </div>
                            <div class="probability-bar">
                                <div class="probability-fill" style="width: ${issue.probability}%"></div>
                                <span class="probability-text">${issue.probability}%</span>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="action-buttons">
                    <button class="btn-primary" onclick="diagnoseFlow.startInteractive()">
                        Zur interaktiven Diagnose
                    </button>
                    <button class="btn-secondary" onclick="diagnoseFlow.scheduleAppointment()">
                        Werkstatt-Termin
                    </button>
                </div>
            </div>
        `;
    }

    animateResults() {
        // Stagger-Animation für Issue-Items
        const items = document.querySelectorAll('.issue-item');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'all 0.4s ease';
                
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        });
    }

    startInteractive() {
        // Weiterleitung zur interaktiven Diagnose
        window.location.href = 'interactive_diagnose.html';
    }

    scheduleAppointment() {
        // Öffne Termin-Buchung
        alert('Termin-Buchung wird geladen...');
    }
}

// Global instance
const diagnoseFlow = new DiagnoseFlow();

// CSS für Animationen (wird dynamisch hinzugefügt)
const styles = `
    <style>
    .diagnose-modal {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center;
        z-index: 1000; animation: fadeIn 0.3s ease;
    }
    
    .loading-container {
        background: white; padding: 40px; border-radius: 20px; text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    
    .ai-animation {
        position: relative; width: 80px; height: 80px; margin: 0 auto 20px;
    }
    
    .brain-pulse {
        width: 100%; height: 100%; border: 3px solid #3498db; border-radius: 50%;
        animation: pulse 1.5s infinite;
    }
    
    .data-streams span {
        position: absolute; width: 2px; height: 20px; background: #3498db;
        animation: stream 1s infinite;
    }
    
    .data-streams span:nth-child(1) { left: 20%; animation-delay: 0s; }
    .data-streams span:nth-child(2) { left: 50%; animation-delay: 0.3s; }
    .data-streams span:nth-child(3) { left: 80%; animation-delay: 0.6s; }
    
    .confidence-card {
        padding: 30px; border-radius: 15px; margin: 20px 0;
        display: flex; align-items: center; gap: 20px;
    }
    
    .confidence-circle {
        width: 80px; height: 80px; border: 4px solid rgba(255,255,255,0.3);
        border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    
    .percentage { font-size: 24px; font-weight: bold; }
    .label { font-size: 12px; opacity: 0.8; }
    
    .issues-list { margin: 20px 0; }
    .issue-item { padding: 15px; margin: 10px 0; border-radius: 10px; background: #f8f9fa; }
    .probability-bar { background: #e9ecef; height: 8px; border-radius: 4px; margin: 8px 0; position: relative; }
    .probability-fill { height: 100%; background: linear-gradient(90deg, #3498db, #2ecc71); border-radius: 4px; transition: width 0.8s ease; }
    .probability-text { position: absolute; right: 0; top: -20px; font-size: 12px; color: #666; }
    
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes pulse { 0% { transform: scale(0.8); opacity: 1; } 50% { transform: scale(1.2); opacity: 0.5; } 100% { transform: scale(0.8); opacity: 1; } }
    @keyframes stream { 0% { height: 0; opacity: 0; } 50% { height: 20px; opacity: 1; } 100% { height: 0; opacity: 0; } }
    </style>
`;

// Styles einfügen
if (!document.querySelector('#diagnose-styles')) {
    const styleSheet = document.createElement('div');
    styleSheet.id = 'diagnose-styles';
    styleSheet.innerHTML = styles;
    document.head.appendChild(styleSheet);
}
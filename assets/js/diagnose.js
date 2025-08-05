class DiagnoseFlow {
    constructor() {
        this.currentStep = 1;
        this.selectedVehicle = null;
        this.selectedSymptoms = [];
        this.diagnosisResult = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupProgressRing();
    }

    bindEvents() {
        // Vehicle selection
        document.querySelectorAll('.vehicle-card').forEach(card => {
            card.addEventListener('click', (e) => {
                this.selectVehicle(e.currentTarget.dataset.vehicle);
            });
        });

        // Symptom selection
        document.querySelectorAll('.symptom-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.selectSymptom(e.currentTarget.dataset.symptom);
            });
        });
    }

    selectVehicle(vehicle) {
        this.selectedVehicle = vehicle;
        document.querySelectorAll('.vehicle-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.querySelector(`[data-vehicle="${vehicle}"]`).classList.add('selected');
        
        setTimeout(() => this.nextStep(), 500);
    }

    selectSymptom(symptom) {
        const btn = document.querySelector(`[data-symptom="${symptom}"]`);
        
        if (this.selectedSymptoms.includes(symptom)) {
            this.selectedSymptoms = this.selectedSymptoms.filter(s => s !== symptom);
            btn.classList.remove('selected');
        } else {
            this.selectedSymptoms.push(symptom);
            btn.classList.add('selected');
        }

        if (this.selectedSymptoms.length > 0) {
            setTimeout(() => this.nextStep(), 800);
        }
    }

    nextStep() {
        const currentStepEl = document.querySelector(`#step-${this.getStepName(this.currentStep)}`);
        currentStepEl.classList.remove('active');
        
        this.currentStep++;
        
        const nextStepEl = document.querySelector(`#step-${this.getStepName(this.currentStep)}`);
        nextStepEl.classList.add('active');

        if (this.currentStep === 3) {
            this.startAnalysis();
        }
    }

    getStepName(step) {
        const steps = {
            1: 'vehicle',
            2: 'symptoms',
            3: 'analysis',
            4: 'result'
        };
        return steps[step];
    }

    startAnalysis() {
        // Simulate AI analysis
        setTimeout(() => {
            this.generateDiagnosis();
            this.nextStep();
        }, 3000);
    }

    generateDiagnosis() {
        // Mock diagnosis based on symptoms
        const mockDiagnoses = {
            'motor-geraeusche': [
                { name: 'Defekte Zündspule', probability: 85 },
                { name: 'Verschleißte Zündkerzen', probability: 72 },
                { name: 'Kraftstofffilter verstopft', probability: 45 }
            ],
            'startprobleme': [
                { name: 'Schwache Batterie', probability: 92 },
                { name: 'Anlasser defekt', probability: 78 },
                { name: 'Zündschloss defekt', probability: 65 }
            ],
            'leistungsverlust': [
                { name: 'Luftfilter verschmutzt', probability: 88 },
                { name: 'Katalysator verstopft', probability: 75 },
                { name: 'Turbolader defekt', probability: 60 }
            ],
            'warnleuchten': [
                { name: 'Motorsteuergerät Fehler', probability: 95 },
                { name: 'Sensoren defekt', probability: 80 },
                { name: 'Kabelbaum beschädigt', probability: 55 }
            ]
        };

        let combinedDiagnoses = [];
        this.selectedSymptoms.forEach(symptom => {
            if (mockDiagnoses[symptom]) {
                combinedDiagnoses = combinedDiagnoses.concat(mockDiagnoses[symptom]);
            }
        });

        // Calculate confidence based on symptom match
        const confidence = Math.min(95, 50 + (this.selectedSymptoms.length * 15));
        
        this.diagnosisResult = {
            confidence: confidence,
            diagnoses: combinedDiagnoses.slice(0, 3)
        };

        this.displayResults();
    }

    displayResults() {
        const confidenceEl = document.querySelector('.percentage');
        const progressRing = document.querySelector('.progress-ring__progress');
        
        // Animate confidence percentage
        this.animateValue(confidenceEl, 0, this.diagnosisResult.confidence, 1000);
        
        // Animate progress ring
        const circumference = 2 * Math.PI * 52;
        const offset = circumference - (this.diagnosisResult.confidence / 100) * circumference;
        
        setTimeout(() => {
            progressRing.style.strokeDashoffset = offset;
            
            // Update color based on confidence
            const gradient = document.querySelector('#gradient');
            const stops = gradient.querySelectorAll('stop');
            
            if (this.diagnosisResult.confidence < 50) {
                stops[0].style.stopColor = '#ff4444';
                stops[1].style.stopColor = '#ff6666';
                stops[2].style.stopColor = '#ff8888';
            } else if (this.diagnosisResult.confidence < 80) {
                stops[0].style.stopColor = '#ffaa00';
                stops[1].style.stopColor = '#ffcc00';
                stops[2].style.stopColor = '#ffdd00';
            } else {
                stops[0].style.stopColor = '#00ff88';
                stops[1].style.stopColor = '#00ffaa';
                stops[2].style.stopColor = '#00ffcc';
            }
        }, 500);

        // Display diagnoses
        const detailsContainer = document.querySelector('.diagnosis-details');
        detailsContainer.innerHTML = '<h3>Mögliche Diagnose</h3>';
        
        this.diagnosisResult.diagnoses.forEach(diagnosis => {
            const item = document.createElement('div');
            item.className = 'diagnosis-item';
            item.innerHTML = `
                <span class="diagnosis-name">${diagnosis.name}</span>
                <span class="diagnosis-probability">${diagnosis.probability}%</span>
            `;
            detailsContainer.appendChild(item);
        });
    }

    animateValue(element, start, end, duration) {
        const range = end - start;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const value = Math.floor(start + (range * progress));
            
            element.textContent = value + '%';
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    setupProgressRing() {
        const circle = document.querySelector('.progress-ring__progress');
        const radius = circle.r.baseVal.value;
        const circumference = 2 * Math.PI * radius;
        
        circle.style.strokeDasharray = `${circumference} ${circumference}`;
        circle.style.strokeDashoffset = circumference;
    }
}

// Global functions
function goToInteractiveDiagnosis() {
    window.location.href = 'interactive-diagnose.php';
}

function restartDiagnosis() {
    window.location.reload();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DiagnoseFlow();
});

// PWA functionality
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
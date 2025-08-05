class CarDiagnose {
    constructor() {
        this.currentCategory = null;
        this.currentQuestion = 0;
        this.answers = {};
        this.questions = this.loadQuestions();
        this.totalQuestions = 20;
        this.answeredQuestions = 0;
        
        this.init();
    }

    loadQuestions() {
        return {
            motor: [
                {
                    text: "Läuft der Motor rau beim Start?",
                    help: "Beobachten Sie das Startverhalten: Ein rauer Start zeigt sich durch ungleichmäßige Drehzahl, Vibrationen oder auffällige Geräusche in den ersten Sekunden."
                },
                {
                    text: "Hören Sie ungewöhnliche Geräusche aus dem Motorraum?",
                    help: "Klopfen, Klappern oder Pfeifen sind typische Warnzeichen. Achten Sie besonders beim Kaltstart und beim Beschleunigen."
                },
                {
                    text: "Leuchtet die Motor-Kontrollleuchte?",
                    help: "Die gelbe Motorkontrollleuchte (Check Engine) zeigt an, dass das Motorsteuergerät einen Fehler erkannt hat."
                },
                {
                    text: "Hat der Motor Leistungsverlust beim Beschleunigen?",
                    help: "Spüren Sie, dass das Auto träge reagiert oder nicht die gewohnte Leistung bringt? Testen Sie beim Überholen."
                },
                {
                    text: "Verbraucht Ihr Fahrzeug mehr Kraftstoff als gewöhnlich?",
                    help: "Vergleichen Sie den aktuellen Verbrauch mit früheren Werten. Ein deutlicher Anstieg kann auf Motorprobleme hindeuten."
                }
            ],
            bremsen: [
                {
                    text: "Quietschen die Bremsen beim Bremsen?",
                    help: "Hohe Quietsch- oder Kreischgeräusche deuten oft auf verschlissene Bremsbeläge hin. Achten Sie auf metallische Geräusche."
                },
                {
                    text: "Zieht das Auto beim Bremsen nach einer Seite?",
                    help: "Fährt das Auto nicht geradeaus, wenn Sie bremsen? Das kann auf unterschiedlich starke Bremswirkung hinweisen."
                },
                {
                    text: "Ist das Bremspedal weicher oder tiefer als gewöhnlich?",
                    help: "Ein zu weiches Pedal oder längerer Weg bis zum Bremsen kann auf Luft im System oder Verschleiß hindeuten."
                },
                {
                    text: "Vibriert das Bremspedal beim Bremsen?",
                    help: "Stärkere Vibrationen oder Pulsieren im Pedal können auf verzogene Bremsscheiben hinweisen."
                },
                {
                    text: "Leuchtet die Bremswarnleuchte?",
                    help: "Die rote Bremswarnleuchte kann auf niedrigen Bremsflüssigkeitsstand oder andere Bremsprobleme hinweisen."
                }
            ],
            fahrwerk: [
                {
                    text: "Hört sich das Fahrwerk beim Überfahren von Bodenwellen an?",
                    help: "Klirrende, klappernde oder quietschende Geräusche deuten auf Verschleiß in Stoßdämpfern oder Gelenken hin."
                },
                {
                    text: "Schwankt das Auto stark in Kurven?",
                    help: "Stärkere Neigung in Kurven oder schwammiges Fahrverhalten können auf defekte Stoßdämpfer hinweisen."
                },
                {
                    text: "Ist die Lenkung unpräzise oder schwergängig?",
                    help: "Spiel im Lenkrad oder ungleichmäßige Lenkreaktion können auf Fahrwerksprobleme hinweisen."
                },
                {
                    text: "Vibriert das Lenkrad bei bestimmten Geschwindigkeiten?",
                    help: "Lenkradvibrationen, besonders zwischen 80-120 km/h, können auf Unwucht oder Fahrwerksprobleme hinweisen."
                },
                {
                    text: "Ist ein Reifen ungleichmäßig abgefahren?",
                    help: "Unregelmäßiger Reifenverschleiß (einsetig, wellig) deutet auf Fahrwerks- oder Achsprobleme hin."
                }
            ],
            elektronik: [
                {
                    text: "Funktionieren alle Lichter ordnungsgemäß?",
                    help: "Prüfen Sie alle Scheinwerfer, Rücklichter, Blinker und Bremslichter auf ordnungsgemäße Funktion."
                },
                {
                    text: "Startet das Fahrzeug zuverlässig?",
                    help: "Langsames Starten oder mehrere Versuche deuten auf Schwäche der Batterie oder des Ladegeräts hin."
                },
                {
                    text: "Flackern die Lichter beim Fahren?",
                    help: "Flackernde Innen- oder Außenbeleuchtung kann auf Ladeprobleme oder elektrische Fehler hinweisen."
                },
                {
                    text: "Funktionieren alle elektrischen Verbraucher?",
                    help: "Prüfen Sie Fensterheber, Klimaanlage, Radio und andere elektrische Komponenten auf einwandfreie Funktion."
                },
                {
                    text: "Leuchtet die Batteriewarnleuchte?",
                    help: "Die rote Batteriewarnleuchte zeigt Probleme mit dem Ladesystem oder der Batterie an."
                }
            ]
        };
    }

    init() {
        this.bindEvents();
        this.updateProgress();
    }

    bindEvents() {
        // Kategorie-Auswahl
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('click', (e) => {
                this.selectCategory(e.target.closest('.category-card').dataset.category);
            });
        });

        // Antwort-Buttons
        document.querySelectorAll('.answer-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const answer = e.target.closest('.answer-btn').dataset.answer;
                this.handleAnswer(answer);
            });
        });

        // Hilfe-System
        document.querySelectorAll('.help-trigger').forEach(btn => {
            btn.addEventListener('click', () => this.showHelp());
        });

        document.getElementById('closeHelp').addEventListener('click', () => this.hideHelp());
        document.getElementById('backBtn').addEventListener('click', () => this.goBack());
        document.getElementById('restartDiagnose').addEventListener('click', () => this.restart());
    }

    selectCategory(category) {
        this.currentCategory = category;
        this.currentQuestion = 0;
        
        document.getElementById('categorySelection').style.display = 'none';
        document.getElementById('questionArea').style.display = 'block';
        
        this.updateQuestion();
    }

    updateQuestion() {
        const question = this.questions[this.currentCategory][this.currentQuestion];
        document.getElementById('questionText').textContent = question.text;
        document.getElementById('helpText').textContent = question.help;
        document.getElementById('categoryTitle').textContent = 
            this.currentCategory.charAt(0).toUpperCase() + this.currentCategory.slice(1) + '-Diagnose';
        document.getElementById('questionCounter').textContent = 
            `Frage ${this.currentQuestion + 1}/5`;
    }

    handleAnswer(answer) {
        if (!this.answers[this.currentCategory]) {
            this.answers[this.currentCategory] = [];
        }
        
        this.answers[this.currentCategory][this.currentQuestion] = answer;
        this.answeredQuestions++;
        
        this.updateProgress();
        this.calculateDiagnosis();
        
        if (this.currentQuestion < 4) {
            this.currentQuestion++;
            this.updateQuestion();
        } else {
            this.showResults();
        }
    }

    updateProgress() {
        const progress = (this.answeredQuestions / this.totalQuestions) * 100;
        document.getElementById('progressFill').style.width = `${progress}%`;
        document.getElementById('progressText').textContent = 
            `${this.answeredQuestions}/${this.totalQuestions} Fragen`;
    }

    calculateDiagnosis() {
        // Hier würde die komplexe Diagnose-Logik implementiert
        // Für jetzt: Einfache Berechnung basierend auf Antworten
        let issues = [];
        
        Object.keys(this.answers).forEach(category => {
            const categoryAnswers = this.answers[category];
            const yesCount = categoryAnswers.filter(a => a === 'yes').length;
            
            if (yesCount > 0) {
                issues.push({
                    category: category,
                    severity: yesCount,
                    probability: Math.min(yesCount * 20, 95)
                });
            }
        });

        this.currentIssues = issues;
    }

    showResults() {
        document.getElementById('questionArea').style.display = 'none';
        document.getElementById('resultsArea').style.display = 'block';
        
        const confidence = Math.max(60, Math.min(95, 100 - (this.currentIssues.length * 10)));
        document.getElementById('confidenceText').textContent = `${confidence}% Sicherheit`;
        document.getElementById('confidenceFill').style.width = `${confidence}%`;
        
        // Farbe basierend auf Sicherheit
        const fill = document.getElementById('confidenceFill');
        fill.className = 'confidence-fill';
        if (confidence < 50) fill.classList.add('low');
        else if (confidence < 80) fill.classList.add('medium');
        else fill.classList.add('high');

        this.renderIssues();
    }

    renderIssues() {
        const issueList = document.getElementById('issueList');
        issueList.innerHTML = '';
        
        this.currentIssues.forEach(issue => {
            const issueDiv = document.createElement('div');
            issueDiv.className = 'issue-item';
            issueDiv.innerHTML = `
                <div class="issue-category">${this.getCategoryName(issue.category)}</div>
                <div class="issue-probability">${issue.probability}% Wahrscheinlichkeit</div>
                <div class="issue-severity">
                    ${'🔴'.repeat(Math.ceil(issue.severity / 2))}
                </div>
            `;
            issueList.appendChild(issueDiv);
        });
    }

    getCategoryName(category) {
        const names = {
            motor: 'Motorprobleme',
            bremsen: 'Bremsenprobleme',
            fahrwerk: 'Fahrwerksprobleme',
            elektronik: 'Elektronikprobleme'
        };
        return names[category] || category;
    }

    showHelp() {
        document.getElementById('helpBox').style.display = 'block';
    }

    hideHelp() {
        document.getElementById('helpBox').style.display = 'none';
    }

    goBack() {
        if (this.currentQuestion > 0) {
            this.currentQuestion--;
            this.updateQuestion();
        } else {
            document.getElementById('questionArea').style.display = 'none';
            document.getElementById('categorySelection').style.display = 'block';
        }
    }

    restart() {
        this.currentCategory = null;
        this.currentQuestion = 0;
        this.answers = {};
        this.answeredQuestions = 0;
        
        document.getElementById('resultsArea').style.display = 'none';
        document.getElementById('categorySelection').style.display = 'block';
        this.updateProgress();
    }
}

// Initialisierung
document.addEventListener('DOMContentLoaded', () => {
    new CarDiagnose();
});
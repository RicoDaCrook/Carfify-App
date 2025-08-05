// Diagnose-Fragen für jede Kategorie
const diagnoseQuestions = {
    motor: [
        {
            id: 'm1',
            question: 'Macht der Motor ungewöhnliche Geräusche beim Start?',
            help: 'Achten Sie auf klopfende, klickende oder quietschende Geräusche beim Starten des Motors. Diese können auf Probleme mit der Zündung, Ventilen oder anderen Motor-Komponenten hinweisen.'
        },
        {
            id: 'm2',
            question: 'Ist die Motorleistung reduziert oder zögerlich?',
            help: 'Fahren Sie eine Probefahrt und achten Sie darauf, ob das Fahrzeug schwächer beschleunigt oder zögerlich reagiert. Dies kann auf Probleme mit der Kraftstoffzufuhr, Zündung oder Abgasanlage hinweisen.'
        },
        {
            id: 'm3',
            question: 'Zeigt die Motorkontrollleuchte Fehler an?',
            help: 'Die Motorkontrollleuchte (gelbes Motor-Symbol) sollte beim Start kurz aufleuchten und dann erlöschen. Bleibt sie an oder blinkt sie, liegt ein Fehler vor. Ein OBD-Scanner kann helfen.'
        },
        {
            id: 'm4',
            question: 'Hat der Motor ungleichmäßigen Leerlauf?',
            help: 'Beobachten Sie die Drehzahlanzeige im Leerlauf. Sie sollte konstant bleiben. Schwankungen oder ein unruhiger Motor können auf Probleme mit der Einspritzung, Zündkerzen oder Luftfilter hinweisen.'
        },
        {
            id: 'm5',
            question: 'Verliert der Motor Öl oder Kühlflüssigkeit?',
            help: 'Kontrollieren Sie regelmäßig den Öl- und Kühlflüssigkeitsstand. Verluste können auf Dichtungsprobleme oder undichte Stellen hinweisen. Achten Sie auf Öl- oder Kühlmittelflecken unter dem Fahrzeug.'
        }
    ],
    bremsen: [
        {
            id: 'b1',
            question: 'Ist die Bremsleistung reduziert oder weich?',
            help: 'Testen Sie die Bremsen in sicherer Umgebung. Der Bremsweg sollte nicht länger als gewohnt sein. Ein weicher Bremspedal kann auf Luft im System oder Verschleiß hinweisen.'
        },
        {
            id: 'b2',
            question: 'Machen die Bremsen Geräusche (quietsch, knirschen)?',
            help: 'Achten Sie auf metallisches Quietschen beim Bremsen - dies ist oft ein Verschleißindikator. Knirschen oder Schleifen kann auf durchgerostete Bremsscheiben oder -beläge hinweisen.'
        },
        {
            id: 'b3',
            question: 'Zieht das Fahrzeug beim Bremsen nach einer Seite?',
            help: 'Fahren Sie geradeaus und bremsen Sie sanft. Zieht das Fahrzeug nach links oder rechts, kann dies auf ungleichmäßigen Verschleiß der Bremsen oder eine defekte Bremsscheibe hinweisen.'
        },
        {
            id: 'b4',
            question: 'Ist die Bremsflüssigkeit auf dem richtigen Stand?',
            help: 'Kontrollieren Sie den Bremsflüssigkeitsbehälter im Motorraum. Der Stand sollte zwischen MIN und MAX liegen. Zu wenig Flüssigkeit kann auf Leckagen hinweisen.'
        },
        {
            id: 'b5',
            question: 'Vibriert das Bremspedal beim Bremsen?',
            help: 'Ein vibrierendes Bremspedal kann auf verzogene Bremsscheiben oder Probleme mit der ABS-Anlage hinweisen. Dies sollte professionell geprüft werden.'
        }
    ],
    fahrwerk: [
        {
            id: 'f1',
            question: 'Macht das Fahrwerk ungewöhnliche Geräusche?',
            help: 'Achten Sie auf klopfende, quietschende oder knarrende Geräusche besonders bei Bodenwellen oder Schlaglöchern. Dies kann auf verschlissene Stoßdämpfer oder Gelenke hinweisen.'
        },
        {
            id: 'f2',
            question: 'Ist die Federung zu weich oder zu hart?',
            help: 'Bei normaler Beladung sollte das Fahrzeug nicht durchfedern. Zu weiche Federung kann auf verschlissene Stoßdämpfer hinweisen, zu harte auf defekte Federn.'
        },
        {
            id: 'f3',
            question: 'Zieht das Fahrzeug bei Geradeausfahrt nach einer Seite?',
            help: 'Fahren Sie auf gerader, ebener Straße und lassen Sie das Lenkrad los. Zieht das Fahrzeug nach links oder rechts, kann dies auf Achsvermessung oder Fahrwerksprobleme hinweisen.'
        },
        {
            id: 'f4',
            question: 'Ist die Lenkung unpräzise oder schwergängig?',
            help: 'Die Lenkung sollte präzise und ohne Spiel reagieren. Schwergängige Lenkung kann auf Probleme mit der Servolenkung oder Gelenken hinweisen.'
        },
        {
            id: 'f5',
            question: 'Sind Reifen ungleichmäßig abgefahren?',
            help: 'Kontrollieren Sie die Reifen auf ungleichmäßigen Verschleiß. Innen- oder Außenverschleiß kann auf Fahrwerksprobleme oder falsche Achsvermessung hinweisen.'
        }
    ],
    elektronik: [
        {
            id: 'e1',
            question: 'Funktionieren alle Lichter korrekt?',
            help: 'Prüfen Sie alle Lichter: Abblendlicht, Fernlicht, Bremslichter, Blinker, Rücklichter. Defekte Lampen können auf Elektrikprobleme oder einfach nur durchgebrannte Birnen hinweisen.'
        },
        {
            id: 'e2',
            question: 'Startet das Fahrzeug zuverlässig?',
            help: 'Beim Starten sollte der Motor sofort anspringen. Zögerliches Starten oder mehrere Versuche können auf eine schwache Batterie, Probleme mit der Zündung oder dem Anlasser hinweisen.'
        },
        {
            id: 'e3',
            question: 'Funktionieren alle elektrischen Fensterheber?',
            help: 'Testen Sie alle Fensterheber. Langsame oder stockende Bewegungen können auf Probleme mit dem Motor oder der Elektrik hinweisen. Achten Sie auf ungewöhnliche Geräusche.'
        },
        {
            id: 'e4',
            question: 'Zeigt das Bordnetz Fehleranzeigen?',
            help: 'Beobachten Sie alle Warnleuchten im Cockpit. Außer einigen Kontrollleuchten beim Start sollten keine weiteren Anzeigen leuchten. Blinkende oder durchgehende Warnleuchten deuten auf Probleme hin.'
        },
        {
            id: 'e5',
            question: 'Funktionieren Klimaanlage und Heizung korrekt?',
            help: 'Testen Sie alle Stufen der Klimaanlage und Heizung. Schwache Luftströme, unangenehme Gerüche oder fehlende Kälte können auf Probleme mit der Klimaanlage, dem Gebläse oder Filtern hinweisen.'
        }
    ]
};

// Globale Variablen
let currentAnswers = {};
let totalQuestions = 20;
let answeredQuestions = 0;

// Initialisierung
document.addEventListener('DOMContentLoaded', function() {
    initializeDiagnosis();
    setupEventListeners();
    updateProgress();
});

function initializeDiagnosis() {
    // Fragen für jede Kategorie laden
    loadQuestions('motor');
    loadQuestions('bremsen');
    loadQuestions('fahrwerk');
    loadQuestions('elektronik');
    
    // Gespeicherte Antworten laden
    const saved = localStorage.getItem('diagnoseAnswers');
    if (saved) {
        currentAnswers = JSON.parse(saved);
        answeredQuestions = Object.keys(currentAnswers).length;
    }
}

function loadQuestions(category) {
    const container = document.getElementById(category + 'Questions');
    const questions = diagnoseQuestions[category];
    
    container.innerHTML = '';
    questions.forEach((q, index) => {
        const questionDiv = createQuestionElement(q, category, index);
        container.appendChild(questionDiv);
    });
}

function createQuestionElement(question, category, index) {
    const div = document.createElement('div');
    div.className = 'question-item';
    div.innerHTML = `
        <div class="question-text">${question.question}</div>
        <div class="answer-options">
            <label class="answer-option">
                <input type="radio" name="${question.id}" value="ja" onchange="handleAnswer('${question.id}', 'ja')">
                <span>Ja</span>
            </label>
            <label class="answer-option">
                <input type="radio" name="${question.id}" value="nein" onchange="handleAnswer('${question.id}', 'nein')">
                <span>Nein</span>
            </label>
            <label class="answer-option">
                <input type="radio" name="${question.id}" value="weiss-nicht" onchange="handleAnswer('${question.id}', 'weiss-nicht')">
                <span>Weiß nicht</span>
            </label>
            <button class="help-btn" onclick="showHelp('${question.id}')">?</button>
        </div>
    `;
    
    // Gespeicherte Antwort markieren
    if (currentAnswers[question.id]) {
        const radio = div.querySelector(`input[value="${currentAnswers[question.id]}"]`);
        if (radio) radio.checked = true;
    }
    
    return div;
}

function setupEventListeners() {
    // Tab-Wechsel
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchCategory(this.dataset.category);
        });
    });
}

function switchCategory(category) {
    // Aktiven Tab wechseln
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.category-content').forEach(content => content.classList.remove('active'));
    
    document.querySelector(`[data-category="${category}"]`).classList.add('active');
    document.getElementById(category).classList.add('active');
}

function handleAnswer(questionId, answer) {
    currentAnswers[questionId] = answer;
    answeredQuestions = Object.keys(currentAnswers).length;
    
    // Fortschritt aktualisieren
    updateProgress();
    
    // Antwort speichern
    localStorage.setItem('diagnoseAnswers', JSON.stringify(currentAnswers));
    
    // Diagnose aktualisieren
    updateDiagnosis();
    
    // Bei "Weiß nicht" Hilfe anzeigen
    if (answer === 'weiss-nicht') {
        showHelp(questionId);
    }
}

function updateProgress() {
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    const percentage = (answeredQuestions / totalQuestions) * 100;
    progressFill.style.width = percentage + '%';
    progressText.textContent = `${answeredQuestions}/${totalQuestions} Fragen`;
    
    // Bei 100% Diagnose anzeigen
    if (answeredQuestions === totalQuestions) {
        showDiagnosisResult();
    }
}

function showHelp(questionId) {
    const modal = document.getElementById('helpModal');
    const helpContent = document.getElementById('helpContent');
    
    // Frage finden
    let helpText = '';
    for (const category in diagnoseQuestions) {
        const question = diagnoseQuestions[category].find(q => q.id === questionId);
        if (question) {
            helpText = question.help;
            break;
        }
    }
    
    helpContent.innerHTML = `<p>${helpText}</p>`;
    modal.style.display = 'block';
}

function closeHelpModal() {
    document.getElementById('helpModal').style.display = 'none';
}

function updateDiagnosis() {
    // Hier würde die eigentliche Diagnose-Logik implementiert
    // Für jetzt: Einfache Auswertung basierend auf Antworten
    console.log('Diagnose aktualisiert:', currentAnswers);
}

function showDiagnosisResult() {
    const resultDiv = document.getElementById('diagnosisResult');
    const resultContent = document.getElementById('resultContent');
    
    // Diagnose auswerten
    const diagnosis = evaluateDiagnosis();
    
    resultContent.innerHTML = `
        <div class="diagnosis-summary">
            <h4>Zusammenfassung</h4>
            <p><strong>Kritische Probleme:</strong> ${diagnosis.critical}</p>
            <p><strong>Wartung erforderlich:</strong> ${diagnosis.maintenance}</p>
            <p><strong>Empfehlung:</strong> ${diagnosis.recommendation}</p>
        </div>
    `;
    
    resultDiv.style.display = 'block';
    document.querySelector('.questions-container').style.display = 'none';
}

function evaluateDiagnosis() {
    let critical = 0;
    let maintenance = 0;
    
    // Einfache Auswertung: Zähle Ja-Antworten
    for (const [questionId, answer] of Object.entries(currentAnswers)) {
        if (answer === 'ja') {
            // Frage-ID analysieren für Kategorie
            if (questionId.startsWith('m')) critical++;
            else if (questionId.startsWith('b')) critical++;
            else if (questionId.startsWith('f')) maintenance++;
            else if (questionId.startsWith('e')) maintenance++;
        }
    }
    
    return {
        critical: critical,
        maintenance: maintenance,
        recommendation: critical > 0 ? 'Sofortige Werkstatt-Aufsuchen' : 'Regelmäßige Wartung empfohlen'
    };
}

function resetDiagnosis() {
    currentAnswers = {};
    answeredQuestions = 0;
    localStorage.removeItem('diagnoseAnswers');
    
    // UI zurücksetzen
    document.getElementById('diagnosisResult').style.display = 'none';
    document.querySelector('.questions-container').style.display = 'block';
    
    // Radio-Buttons zurücksetzen
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.checked = false;
    });
    
    updateProgress();
}

// Modal außerhalb schließen
window.onclick = function(event) {
    const modal = document.getElementById('helpModal');
    if (event.target === modal) {
        closeHelpModal();
    }
};
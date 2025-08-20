<?php
/**
 * Carfify AI v4.0 - Detektiv Diagnose System
 * Meister Müller - 30 Jahre Erfahrung als Kfz-Detektiv
 * 
 * Dieses System führt eine detektivische Diagnose durch,
 * ähnlich wie ein erfahrener Kfz-Meister vorgehen würde.
 */

class DetektivDiagnose {
    
    private $fahrzeug = null;
    private $diagnoseSicherheit = 0;
    private $symptome = [];
    private $historie = [];
    private $fahrprofil = [];
    private $kritischeAntworten = [];
    private $wahrscheinlichkeiten = [];
    private $aktuelleFrage = 0;
    private $diagnoseLog = [];
    
    public function __construct() {
        session_start();
        $this->initDiagnose();
    }
    
    private function initDiagnose() {
        if (!isset($_SESSION['diagnose'])) {
            $_SESSION['diagnose'] = [
                'schritt' => 'fahrzeug-check',
                'fahrzeug' => null,
                'symptome' => [],
                'historie' => [],
                'fahrprofil' => [],
                'wahrscheinlichkeiten' => [],
                'sicherheit' => 0,
                'kritische_antworten' => []
            ];
        }
    }
    
    /**
     * Haupt-Einstiegspunkt für die Diagnose
     */
    public function startDiagnose() {
        if (!$this->pruefeFahrzeug()) {
            return $this->renderFahrzeugEingabe();
        }
        
        return $this->renderDiagnoseInterface();
    }
    
    /**
     * Prüft ob ein Fahrzeug hinterlegt ist
     */
    private function pruefeFahrzeug() {
        return isset($_SESSION['diagnose']['fahrzeug']) && 
               !empty($_SESSION['diagnose']['fahrzeug']['marke']) &&
               !empty($_SESSION['diagnose']['fahrzeug']['modell']) &&
               !empty($_SESSION['diagnose']['fahrzeug']['baujahr']);
    }
    
    /**
     * Rendert die Fahrzeug-Eingabe
     */
    private function renderFahrzeugEingabe() {
        ob_start();
        ?>
        <div class="detektiv-container">
            <div class="meister-intro">
                <h2>🕵️ Meister Müller - Ihr Kfz-Detektiv</h2>
                <p class="intro-text">
                    "Guten Tag! Ich bin Meister Müller, seit 30 Jahren im Geschäft. 
                    Bevor ich Ihnen helfen kann, brauche ich Ihre Fahrzeugdaten - 
                    ohne diese Information kann keine fundierte Diagnose erfolgen."
                </p>
            </div>
            
            <form id="fahrzeug-form" class="detektiv-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Marke *</label>
                        <select name="marke" required>
                            <option value="">Bitte wählen...</option>
                            <option value="VW">Volkswagen</option>
                            <option value="BMW">BMW</option>
                            <option value="Mercedes">Mercedes-Benz</option>
                            <option value="Audi">Audi</option>
                            <option value="Opel">Opel</option>
                            <option value="Ford">Ford</option>
                            <option value="Toyota">Toyota</option>
                            <option value="Honda">Honda</option>
                            <option value="Renault">Renault</option>
                            <option value="Peugeot">Peugeot</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Modell *</label>
                        <input type="text" name="modell" required 
                               placeholder="z.B. Golf VII, 3er Touring">
                    </div>
                    
                    <div class="form-group">
                        <label>Baujahr *</label>
                        <select name="baujahr" required>
                            <option value="">Bitte wählen...</option>
                            <?php for($j = date('Y'); $j >= 1990; $j--): ?>
                                <option value="<?= $j ?>"><?= $j ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Motor</label>
                        <input type="text" name="motor" 
                               placeholder="z.B. 2.0 TDI, 1.6 16V">
                    </div>
                    
                    <div class="form-group">
                        <label>Kilometerstand</label>
                        <input type="number" name="kilometer" 
                               placeholder="Aktueller Kilometerstand">
                    </div>
                </div>
                
                <button type="submit" class="detektiv-btn primary">
                    Diagnose starten
                </button>
            </form>
        </div>
        
        <style>
            .detektiv-container {
                max-width: 800px;
                margin: 0 auto;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            .meister-intro {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                color: white;
                padding: 2rem;
                border-radius: 10px;
                margin-bottom: 2rem;
            }
            
            .intro-text {
                font-style: italic;
                font-size: 1.1rem;
                line-height: 1.6;
            }
            
            .form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: #333;
            }
            
            .form-group input,
            .form-group select {
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #ddd;
                border-radius: 5px;
                font-size: 1rem;
            }
            
            .detektiv-btn {
                padding: 1rem 2rem;
                border: none;
                border-radius: 5px;
                font-size: 1.1rem;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .detektiv-btn.primary {
                background: #2a5298;
                color: white;
            }
            
            .detektiv-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
        </style>
        
        <script>
            document.getElementById('fahrzeug-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('diagnose/api.php?action=saveFahrzeug', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendert das Haupt-Diagnose-Interface
     */
    private function renderDiagnoseInterface() {
        ob_start();
        $fahrzeug = $_SESSION['diagnose']['fahrzeug'];
        ?>
        <div class="detektiv-workspace">
            <!-- Header mit Fahrzeug-Info -->
            <div class="detektiv-header">
                <div class="fahrzeug-info">
                    <h3>🔍 Detektiv-Diagnose für:</h3>
                    <p class="fahrzeug-details">
                        <?= htmlspecialchars($fahrzeug['marke'] . ' ' . $fahrzeug['modell']) ?> 
                        (<?= $fahrzeug['baujahr'] ?>)
                        <?php if (!empty($fahrzeug['kilometer'])): ?>
                            - <?= number_format($fahrzeug['kilometer'], 0, ',', '.') ?> km
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="diagnose-sicherheit">
                    <div class="sicherheit-label">Diagnose-Sicherheit:</div>
                    <div class="sicherheit-bar">
                        <div class="sicherheit-fill" style="width: <?= $_SESSION['diagnose']['sicherheit'] ?>%">
                            <?= $_SESSION['diagnose']['sicherheit'] ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Problem-Kacheln -->
            <div class="problem-kacheln">
                <h4>Wählen Sie Ihr Problemgebiet:</h4>
                <div class="kachel-grid">
                    <div class="problem-kachel" data-kategorie="motor">
                        <div class="kachel-icon">🔧</div>
                        <div class="kachel-title">Motor</div>
                        <div class="kachel-desc">Ruckeln, Leistungsverlust, Geräusche</div>
                    </div>
                    
                    <div class="problem-kachel" data-kategorie="getriebe">
                        <div class="kachel-icon">⚙️</div>
                        <div class="kachel-title">Getriebe</div>
                        <div class="kachel-desc">Schaltprobleme, Vibrationen</div>
                    </div>
                    
                    <div class="problem-kachel" data-kategorie="bremsen">
                        <div class="kachel-icon">🛑</div>
                        <div class="kachel-title">Bremsen</div>
                        <div class="kachel-desc">Quietschen, Schwäche, Pedalgefühl</div>
                    </div>
                    
                    <div class="problem-kachel" data-kategorie="elektrik">
                        <div class="kachel-icon">💡</div>
                        <div class="kachel-title">Elektrik</div>
                        <div class="kachel-desc">Licht, Batterie, Anlasser</div>
                    </div>
                    
                    <div class="problem-kachel" data-kategorie="fahrwerk">
                        <div class="kachel-icon">🛞</div>
                        <div class="kachel-title">Fahrwerk</div>
                        <div class="kachel-desc">Stoßdämpfer, Federn, Achse</div>
                    </div>
                    
                    <div class="problem-kachel" data-kategorie="klima">
                        <div class="kachel-icon">❄️</div>
                        <div class="kachel-title">Klimaanlage</div>
                        <div class="kachel-desc">Keine Kälte, Geräusche, Geruch</div>
                    </div>
                </div>
            </div>
            
            <!-- Live-Wahrscheinlichkeiten -->
            <div class="wahrscheinlichkeiten-container" style="display: none;">
                <h4>Aktuelle Wahrscheinlichkeiten:</h4>
                <div class="wahrscheinlichkeiten-list">
                    <!-- Wird dynamisch gefüllt -->
                </div>
            </div>
            
            <!-- Fragen-Interface -->
            <div class="fragen-container" style="display: none;">
                <div class="frage-box">
                    <div class="frage-text"></div>
                    <div class="antwort-optionen"></div>
                    <div class="upload-section">
                        <h5>Bild hochladen für Analyse:</h5>
                        <input type="file" id="bild-upload" accept="image/*">
                        <div id="upload-preview"></div>
                    </div>
                </div>
            </div>
            
            <!-- Ergebnis-Container -->
            <div class="ergebnis-container" style="display: none;">
                <!-- Wird bei Fertigstellung angezeigt -->
            </div>
        </div>
        
        <style>
            .detektiv-workspace {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            .detektiv-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8f9fa;
                padding: 1.5rem;
                border-radius: 10px;
                margin-bottom: 2rem;
            }
            
            .fahrzeug-info h3 {
                margin: 0 0 0.5rem 0;
                color: #2a5298;
            }
            
            .fahrzeug-details {
                margin: 0;
                font-size: 1.1rem;
                color: #666;
            }
            
            .sicherheit-bar {
                width: 200px;
                height: 20px;
                background: #e0e0e0;
                border-radius: 10px;
                overflow: hidden;
            }
            
            .sicherheit-fill {
                height: 100%;
                background: linear-gradient(90deg, #ff4444 0%, #ffaa00 50%, #00aa00 100%);
                transition: width 0.5s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 0.8rem;
                font-weight: bold;
            }
            
            .problem-kacheln h4 {
                margin-bottom: 1.5rem;
                color: #333;
            }
            
            .kachel-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .problem-kachel {
                background: white;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                padding: 1.5rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .problem-kachel:hover {
                border-color: #2a5298;
                transform: translateY(-3px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            
            .kachel-icon {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }
            
            .kachel-title {
                font-weight: bold;
                margin-bottom: 0.5rem;
            }
            
            .kachel-desc {
                font-size: 0.9rem;
                color: #666;
            }
            
            .wahrscheinlichkeiten-container {
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 10px;
                padding: 1.5rem;
                margin: 2rem 0;
            }
            
            .wahrscheinlichkeit-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            }
            
            .wahrscheinlichkeit-bar {
                flex: 1;
                height: 20px;
                background: #e0e0e0;
                border-radius: 10px;
                margin: 0 1rem;
                overflow: hidden;
            }
            
            .wahrscheinlichkeit-fill {
                height: 100%;
                background: #2a5298;
                transition: width 0.5s ease;
            }
        </style>
        
        <script>
            // Problem-Kachel Auswahl
            document.querySelectorAll('.problem-kachel').forEach(kachel => {
                kachel.addEventListener('click', function() {
                    const kategorie = this.dataset.kategorie;
                    startDiagnoseKategorie(kategorie);
                });
            });
            
            function startDiagnoseKategorie(kategorie) {
                fetch('diagnose/api.php?action=startKategorie', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({kategorie: kategorie})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.problem-kacheln').style.display = 'none';
                        document.querySelector('.wahrscheinlichkeiten-container').style.display = 'block';
                        document.querySelector('.fragen-container').style.display = 'block';
                        ladeNaechsteFrage();
                    }
                });
            }
            
            function ladeNaechsteFrage() {
                fetch('diagnose/api.php?action=getFrage')
                .then(response => response.json())
                .then(data => {
                    if (data.frage) {
                        zeigeFrage(data.frage);
                        aktualisiereWahrscheinlichkeiten(data.wahrscheinlichkeiten);
                    } else {
                        zeigeErgebnis(data.ergebnis);
                    }
                });
            }
            
            function zeigeFrage(frage) {
                const container = document.querySelector('.frage-box');
                container.querySelector('.frage-text').innerHTML = frage.text;
                
                const optionen = container.querySelector('.antwort-optionen');
                optionen.innerHTML = '';
                
                frage.optionen.forEach(option => {
                    const btn = document.createElement('button');
                    btn.className = 'antwort-btn';
                    btn.textContent = option.text;
                    btn.dataset.wert = option.wert;
                    btn.addEventListener('click', () => beantworteFrage(option.wert));
                    optionen.appendChild(btn);
                });
            }
            
            function beantworteFrage(antwort) {
                fetch('diagnose/api.php?action=beantworteFrage', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({antwort: antwort})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.kritisch) {
                        zeigeKritischeRückfrage(data.kritisch);
                    } else {
                        ladeNaechsteFrage();
                    }
                });
            }
            
            function aktualisiereWahrscheinlichkeiten(wahrscheinlichkeiten) {
                const container = document.querySelector('.wahrscheinlichkeiten-list');
                container.innerHTML = '';
                
                wahrscheinlichkeiten.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'wahrscheinlichkeit-item';
                    div.innerHTML = `
                        <span>${item.diagnose}</span>
                        <div class="wahrscheinlichkeit-bar">
                            <div class="wahrscheinlichkeit-fill" style="width: ${item.wahrscheinlichkeit}%"></div>
                        </div>
                        <span>${item.wahrscheinlichkeit}%</span>
                    `;
                    container.appendChild(div);
                });
            }
            
            // Bild-Upload für Vision-API
            document.getElementById('bild-upload').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('upload-preview').innerHTML = 
                            `<img src="${e.target.result}" style="max-width: 200px; margin-top: 1rem;">`;
                        
                        // Upload zur Analyse
                        const formData = new FormData();
                        formData.append('bild', file);
                        
                        fetch('diagnose/api.php?action=analysiereBild', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.analyse) {
                                // Verarbeite Bild-Analyse
                                console.log('Bildanalyse:', data.analyse);
                            }
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * API-Endpunkt für AJAX-Anfragen
     */
    public function handleApiRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'saveFahrzeug':
                return $this->saveFahrzeug();
            case 'startKategorie':
                return $this->startKategorie();
            case 'getFrage':
                return $this->getNaechsteFrage();
            case 'beantworteFrage':
                return $this->beantworteFrage();
            case 'analysiereBild':
                return $this->analysiereBild();
            default:
                return json_encode(['error' => 'Ungültige Aktion']);
        }
    }
    
    private function saveFahrzeug() {
        $_SESSION['diagnose']['fahrzeug'] = [
            'marke' => $_POST['marke'],
            'modell' => $_POST['modell'],
            'baujahr' => $_POST['baujahr'],
            'motor' => $_POST['motor'] ?? '',
            'kilometer' => $_POST['kilometer'] ?? 0
        ];
        
        return json_encode(['success' => true]);
    }
    
    private function startKategorie() {
        $input = json_decode(file_get_contents('php://input'), true);
        $kategorie = $input['kategorie'];
        
        $_SESSION['diagnose']['kategorie'] = $kategorie;
        $_SESSION['diagnose']['schritt'] = 'fragen';
        
        // Initialisiere Wahrscheinlichkeiten basierend auf Fahrzeug und Kategorie
        $this->initialisiereWahrscheinlichkeiten($kategorie);
        
        return json_encode(['success' => true]);
    }
    
    private function initialisiereWahrscheinlichkeiten($kategorie) {
        // Basis-Wahrscheinlichkeiten basierend auf Fahrzeugtyp und Kategorie
        $fahrzeug = $_SESSION['diagnose']['fahrzeug'];
        $wahrscheinlichkeiten = [];
        
        switch ($kategorie) {
            case 'motor':
                $wahrscheinlichkeiten = [
                    ['diagnose' => 'Zündkerzen verschleißt', 'wahrscheinlichkeit' => 25],
                    ['diagnose' => 'Luftmassenmesser defekt', 'wahrscheinlichkeit' => 20],
                    ['diagnose' => 'Kraftstofffilter verstopft', 'wahrscheinlichkeit' => 15],
                    ['diagnose' => 'Lambdasonde fehlerhaft', 'wahrscheinlichkeit' => 10],
                    ['diagnose' => 'Motorsteuergerät Problem', 'wahrscheinlichkeit' => 5]
                ];
                break;
            case 'bremsen':
                $wahrscheinlichkeiten = [
                    ['diagnose' => 'Bremsbeläge verschlissen', 'wahrscheinlichkeit' => 40],
                    ['diagnose' => 'Bremsflüssigkeit alt', 'wahrscheinlichkeit' => 25],
                    ['diagnose' => 'Bremsenscheiben verzogen', 'wahrscheinlichkeit' => 20],
                    ['diagnose' => 'Bremszylinder defekt', 'wahrscheinlichkeit' => 10],
                    ['diagnose' => 'ABS-Sensor fehlerhaft', 'wahrscheinlichkeit' => 5]
                ];
                break;
            // Weitere Kategorien...
        }
        
        $_SESSION['diagnose']['wahrscheinlichkeiten'] = $wahrscheinlichkeiten;
    }
    
    private function getNaechsteFrage() {
        $schritt = $_SESSION['diagnose']['schritt'];
        $kategorie = $_SESSION['diagnose']['kategorie'];
        
        $fragen = $this->getFragenFuerKategorie($kategorie);
        $aktuelleFrageIndex = $_SESSION['diagnose']['aktuelle_frage'] ?? 0;
        
        if ($aktuelleFrageIndex >= count($fragen)) {
            // Diagnose abgeschlossen
            return json_encode(['ergebnis' => $this->berechneEndergebnis()]);
        }
        
        $frage = $fragen[$aktuelleFrageIndex];
        
        return json_encode([
            'frage' => $frage,
            'wahrscheinlichkeiten' => $_SESSION['diagnose']['wahrscheinlichkeiten']
        ]);
    }
    
    private function getFragenFuerKategorie($kategorie) {
        // Intelligente Fragen-Ketten basierend auf Kategorie
        $fragen = [
            'motor' => [
                [
                    'text' => 'Wann tritt das Problem auf?',
                    'optionen' => [
                        ['text' => 'Beim Kaltstart', 'wert' => 'kaltstart'],
                        ['text' => 'Beim Warmstart', 'wert' => 'warmstart'],
                        ['text' => 'Immer', 'wert' => 'immer'],
                        ['text' => 'Nur bei Last', 'wert' => 'last']
                    ]
                ],
                [
                    'text' => 'Haben Sie kürzlich etwas an der Zündanlage gemacht?',
                    'optionen' => [
                        ['text' => 'Ja, Zündkerzen gewechselt', 'wert' => 'kerzen_neu'],
                        ['text' => 'Ja, Zündkabel erneuert', 'wert' => 'kabel_neu'],
                        ['text' => 'Nein, nichts gemacht', 'wert' => 'nichts'],
                        ['text' => 'Unsicher', 'wert' => 'unsicher']
                    ]
                ],
                [
                    'text' => 'Wie hoch ist Ihr durchschnittlicher Verbrauch?',
                    'optionen' => [
                        ['text' => 'Normal wie immer', 'wert' => 'normal'],
                        ['text' => 'Etwas erhöht', 'wert' => 'erhoeht'],
                        ['text' => 'Deutlich erhöht', 'wert' => 'deutlich_erhoeht'],
                        ['text' => 'Weiß ich nicht', 'wert' => 'unbekannt']
                    ]
                ]
            ],
            'bremsen' => [
                [
                    'text' => 'Bei welcher Geschwindigkeit treten die Symptome auf?',
                    'optionen' => [
                        ['text' => 'Nur beim Anfahren', 'wert' => 'anfahren'],
                        ['text' => 'Bei allen Geschwindigkeiten', 'wert' => 'alle'],
                        ['text' => 'Nur bei hohen Geschwindigkeiten', 'wert' => 'hoch'],
                        ['text' => 'Beim Bremsen aus hoher Geschwindigkeit', 'wert' => 'hoch_bremsen']
                    ]
                ],
                [
                    'text' => 'Wann wurden die Bremsen zuletzt gewartet?',
                    'optionen' => [
                        ['text' => 'Vor weniger als 10.000 km', 'wert' => 'neu'],
                        ['text' => 'Vor 10.000-30.000 km', 'wert' => 'mittel'],
                        ['text' => 'Vor mehr als 30.000 km', 'wert' => 'alt'],
                        ['text' => 'Weiß ich nicht', 'wert' => 'unbekannt']
                    ]
                ]
            ]
        ];
        
        return $fragen[$kategorie] ?? [];
    }
    
    private function beantworteFrage() {
        $input = json_decode(file_get_contents('php://input'), true);
        $antwort = $input['antwort'];
        
        // Speichere Antwort
        $_SESSION['diagnose']['antworten'][] = $antwort;
        
        // Aktualisiere Wahrscheinlichkeiten basierend auf Antwort
        $this->aktualisiereWahrscheinlichkeiten($antwort);
        
        // Prüfe auf kritische Antworten
        $kritisch = $this->pruefeKritischeAntwort($antwort);
        
        $_SESSION['diagnose']['aktuelle_frage'] = ($_SESSION['diagnose']['aktuelle_frage'] ?? 0) + 1;
        
        return json_encode([
            'kritisch' => $kritisch,
            'wahrscheinlichkeiten' => $_SESSION['diagnose']['wahrscheinlichkeiten']
        ]);
    }
    
    private function aktualisiereWahrscheinlichkeiten($antwort) {
        $wahrscheinlichkeiten = $_SESSION['diagnose']['wahrscheinlichkeiten'];
        
        // Logik zur Anpassung der Wahrscheinlichkeiten basierend auf Antwort
        foreach ($wahrscheinlichkeiten as &$item) {
            switch ($antwort) {
                case 'kaltstart':
                    if (strpos($item['diagnose'], 'Zündkerzen') !== false) {
                        $item['wahrscheinlichkeit'] += 15;
                    }
                    break;
                case 'deutlich_erhoeht':
                    if (strpos($item['diagnose'], 'Luftmassenmesser') !== false) {
                        $item['wahrscheinlichkeit'] += 20;
                    }
                    break;
                // Weitere Anpassungen...
            }
            
            // Normalisierung auf 100%
            $gesamt = array_sum(array_column($wahrscheinlichkeiten, 'wahrscheinlichkeit'));
            if ($gesamt > 0) {
                $item['wahrscheinlichkeit'] = round(($item['wahrscheinlichkeit'] / $gesamt) * 100);
            }
        }
        
        $_SESSION['diagnose']['wahrscheinlichkeiten'] = $wahrscheinlichkeiten;
        
        // Berechne neue Gesamtsicherheit
        $maxWahrscheinlichkeit = max(array_column($wahrscheinlichkeiten, 'wahrscheinlichkeit'));
        $_SESSION['diagnose']['sicherheit'] = $maxWahrscheinlichkeit;
    }
    
    private function pruefeKritischeAntwort($antwort) {
        $kritischeAntworten = [
            'deutlich_erhoeht' => [
                'text' => 'Sie haben angegeben, der Verbrauch sei deutlich erhöht. 
                          Können Sie das genauer quantifizieren? Wie viel Liter mehr pro 100km?',
                'optionen' => [
                    ['text' => '1-2 Liter mehr', 'wert' => 'leicht_erhoeht'],
                    ['text' => '3-5 Liter mehr', 'wert' => 'mittel_erhoeht'],
                    ['text' => 'Mehr als 5 Liter mehr', 'wert' => 'stark_erhoeht']
                ]
            ]
        ];
        
        return $kritischeAntworten[$antwort] ?? null;
    }
    
    private function analysiereBild() {
        // Hier würde die Vision-API-Analyse stattfinden
        // Für Demo-Zwecke Rückgabe eines Beispiel-Ergebnisses
        
        return json_encode([
            'analyse' => [
                'erkannt' => 'Verschleiß an Bremsbelägen',
                'konfidenz' => 85,
                'empfehlung' => 'Sofortige Werkstatt-Aufnahme empfohlen'
            ]
        ]);
    }
    
    private function berechneEndergebnis() {
        $wahrscheinlichkeiten = $_SESSION['diagnose']['wahrscheinlichkeiten'];
        $fahrzeug = $_SESSION['diagnose']['fahrzeug'];
        
        // Sortiere nach Wahrscheinlichkeit
        usort($wahrscheinlichkeiten, function($a, $b) {
            return $b['wahrscheinlichkeit'] <=> $a['wahrscheinlichkeit'];
        });
        
        $topDiagnose = $wahrscheinlichkeiten[0];
        
        // Generiere Lösungswege
        $loesungswege = $this->generiereLoesungswege($topDiagnose['diagnose']);
        
        return [
            'diagnose' => $topDiagnose['diagnose'],
            'sicherheit' => $topDiagnose['wahrscheinlichkeit'],
            'ursache' => $this->generiereUrsachenErklaerung($topDiagnose['diagnose'], $fahrzeug),
            'kosten' => $this->schaetzeKosten($topDiagnose['diagnose'], $fahrzeug),
            'loesungswege' => $loesungswege,
            'werkstatt_empfohlen' => $topDiagnose['wahrscheinlichkeit'] < 70 || 
                                   $this->istSicherheitsrelevant($topDiagnose['diagnose'])
        ];
    }
    
    private function generiereUrsachenErklaerung($diagnose, $fahrzeug) {
        $kilometer = $fahrzeug['kilometer'] ?? 0;
        $baujahr = $fahrzeug['baujahr'];
        $alter = date('Y') - $baujahr;
        
        $erklaerungen = [
            'Zündkerzen verschleißt' => 
                "Bei {$kilometer}km und einem Alter von {$alter} Jahren sind die Zündkerzen 
                 regulär verschleißt. Die durchschnittliche Lebensdauer beträgt 40.000-60.000km.",
            'Bremsbeläge verschlissen' =>
                "Mit {$kilometer}km sind die Bremsbeläge regulär verschleißt. 
                 Die durchschnittliche Lebensdauer liegt bei 30.000-70.000km je nach Fahrstil."
        ];
        
        return $erklaerungen[$diagnose] ?? "Basierend auf Ihren Angaben und dem Fahrzeugalter.";
    }
    
    private function schaetzeKosten($diagnose, $fahrzeug) {
        $kosten = [
            'Zündkerzen verschleißt' => ['min' => 80, 'max' => 200, 'einheit' => '€'],
            'Bremsbeläge verschlissen' => ['min' => 150, 'max' => 400, 'einheit' => '€'],
            'Luftmassenmesser defekt' => ['min' => 200, 'max' => 600, 'einheit' => '€']
        ];
        
        return $kosten[$diagnose] ?? ['min' => 100, 'max' => 500, 'einheit' => '€'];
    }
    
    private function generiereLoesungswege($diagnose) {
        return [
            [
                'titel' => 'DIY - Selbst reparieren',
                'beschreibung' => 'Für geübte Heimwerker mit passendem Werkzeug',
                'kosten' => 'Materialkosten nur',
                'zeit' => '2-4 Stunden',
                'schwierigkeit' => 'mittel',
                'empfehlung' => 2
            ],
            [
                'titel' => 'Freie Werkstatt',
                'beschreibung' => 'Gute Alternative mit Markenqualität',
                'kosten' => 'Material + Arbeitszeit',
                'zeit' => '1-2 Stunden',
                'schwierigkeit' => 'professionell',
                'empfehlung' => 1
            ],
            [
                'titel' => 'Vertragswerkstatt',
                'beschreibung' => 'Original-Ersatzteile mit Garantie',
                'kosten' => 'Höher, aber mit Garantie',
                'zeit' => '1-2 Stunden',
                'schwierigkeit' => 'professionell',
                'empfehlung' => 3
            ]
        ];
    }
    
    private function istSicherheitsrelevant($diagnose) {
        $sicherheitsrelevant = [
            'Bremsbeläge verschlissen',
            'Bremsflüssigkeit alt',
            'Bremsenscheiben verzogen',
            'Reifenverschleiß'
        ];
        
        return in_array($diagnose, $sicherheitsrelevant);
    }
}

// API-Handler
if (basename($_SERVER['PHP_SELF']) === 'api.php') {
    $diagnose = new DetektivDiagnose();
    header('Content-Type: application/json');
    echo $diagnose->handleApiRequest();
    exit;
}

// Haupt-Interface
$diagnose = new DetektivDiagnose();
echo $diagnose->startDiagnose();
?>
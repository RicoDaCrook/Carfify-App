<?php
require_once 'api/base.php';
require_once 'classes/MeisterMueller.php';

$meister = new MeisterMueller();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - Meister Müller Diagnose</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/diagnosis.css">
</head>
<body>
    <div class="container">
        <header class="diagnosis-header">
            <h1>🔧 Meister Müller hilft Ihnen</h1>
            <p class="subtitle">Ihr Kfz-Profi erklärt alles Schritt für Schritt</p>
        </header>

        <!-- Standortabfrage Modal -->
        <div id="location-modal" class="modal active">
            <div class="modal-content">
                <h2>📍 Ihr Standort für bessere Hilfe</h2>
                <p class="location-explanation">
                    <strong>Warum fragen wir das?</strong><br>
                    Damit ich Ihnen passende Werkstätten in Ihrer Nähe zeigen kann 
                    und die Preise für Ihre Region berechnen kann.
                </p>
                <div class="privacy-notice">
                    <span>🔒 Ihre Daten bleiben anonym und werden nur lokal verwendet</span>
                </div>
                <div class="location-buttons">
                    <button id="allow-location" class="btn btn-primary">
                        Standort erlauben
                    </button>
                    <button id="skip-location" class="btn btn-secondary">
                        Später entscheiden
                    </button>
                </div>
            </div>
        </div>

        <!-- HSN/TSN Eingabe -->
        <div id="hsn-tsn-section" class="section hidden">
            <h2>Fahrzeug identifizieren</h2>
            <div class="input-group">
                <label for="hsn">HSN (Herstellerschlüssel)</label>
                <input type="text" id="hsn" placeholder="z.B. 0005 für VW" maxlength="4">
            </div>
            <div class="input-group">
                <label for="tsn">TSN (Typschlüssel)</label>
                <input type="text" id="tsn" placeholder="z.B. AXX" maxlength="3">
            </div>
            <button id="start-diagnosis" class="btn btn-primary">Diagnose starten</button>
        </div>

        <!-- Drei-Säulen-Layout -->
        <div id="diagnosis-layout" class="three-column-layout hidden">
            <!-- Linke Spalte: Sofort-Fragen -->
            <div class="column left-column">
                <div class="column-header">
                    <h3>🚨 Sofort-Fragen</h3>
                    <div class="safety-score">
                        <span>Sicherheit: </span>
                        <span id="safety-percentage">100%</span>
                        <div class="safety-bar">
                            <div id="safety-fill" class="safety-fill"></div>
                        </div>
                    </div>
                </div>
                <div id="quick-questions" class="questions-container">
                    <!-- Dynamisch gefüllt -->
                </div>
            </div>

            <!-- Mittlere Spalte: Prüfliste -->
            <div class="column middle-column">
                <div class="column-header">
                    <h3>📋 Prüfliste</h3>
                    <p class="meister-tip">Klicken Sie die Kästchen an, wenn Sie's geprüft haben!</p>
                </div>
                <div id="checklist" class="checklist-container">
                    <!-- Dynamisch gefüllt -->
                </div>
            </div>

            <!-- Rechte Spalte: KI-Chat -->
            <div class="column right-column">
                <div class="column-header">
                    <h3>💬 Meister Müller Chat</h3>
                    <button id="voice-toggle" class="btn-icon">🎤</button>
                </div>
                <div id="chat-container" class="chat-container">
                    <div id="chat-messages" class="chat-messages">
                        <div class="message meister">
                            <div class="avatar">👨‍🔧</div>
                            <div class="content">
                                <strong>Meister Müller:</strong><br>
                                Hallo! Ich bin Meister Müller und helfe Ihnen, Ihr Auto zu verstehen. 
                                Stellen Sie mir einfach Ihre Fragen!
                            </div>
                        </div>
                    </div>
                    <div class="chat-input">
                        <input type="text" id="chat-input" placeholder="Was macht Ihr Auto?">
                        <button id="send-message" class="btn-icon">📤</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lösungswege Tabs -->
        <div id="solution-tabs" class="solution-tabs hidden">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="self">🔧 Selbst machen</button>
                <button class="tab-btn" data-tab="hybrid">🤝 Hybrid</button>
                <button class="tab-btn" data-tab="workshop">🏭 Werkstatt</button>
            </div>
            <div id="tab-content" class="tab-content">
                <!-- Dynamisch gefüllt -->
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/diagnosis.js"></script>
</body>
</html>
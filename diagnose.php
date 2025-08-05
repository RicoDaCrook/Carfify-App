<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - Interaktive Diagnose</title>
    <link rel="stylesheet" href="assets/css/diagnose.css">
    <link rel="manifest" href="manifest.json">
</head>
<body>
    <div class="container">
        <header>
            <h1>Fahrzeugdiagnose</h1>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <span class="progress-text" id="progressText">0/20 Fragen</span>
            </div>
        </header>

        <main>
            <div class="diagnose-container">
                <div class="category-tabs">
                    <button class="tab-btn active" data-category="motor">Motor</button>
                    <button class="tab-btn" data-category="bremsen">Bremsen</button>
                    <button class="tab-btn" data-category="fahrwerk">Fahrwerk</button>
                    <button class="tab-btn" data-category="elektronik">Elektronik</button>
                </div>

                <div class="questions-container">
                    <div class="category-content active" id="motor">
                        <h2>Motor-Diagnose</h2>
                        <div class="questions" id="motorQuestions"></div>
                    </div>
                    
                    <div class="category-content" id="bremsen">
                        <h2>Bremsen-Diagnose</h2>
                        <div class="questions" id="bremsenQuestions"></div>
                    </div>
                    
                    <div class="category-content" id="fahrwerk">
                        <h2>Fahrwerk-Diagnose</h2>
                        <div class="questions" id="fahrwerkQuestions"></div>
                    </div>
                    
                    <div class="category-content" id="elektronik">
                        <h2>Elektronik-Diagnose</h2>
                        <div class="questions" id="elektronikQuestions"></div>
                    </div>
                </div>

                <div class="diagnosis-result" id="diagnosisResult" style="display: none;">
                    <h3>Diagnose-Ergebnis</h3>
                    <div id="resultContent"></div>
                    <button class="btn-primary" onclick="resetDiagnosis()">Neue Diagnose</button>
                </div>
            </div>
        </main>
    </div>

    <!-- Hilfe Modal -->
    <div class="modal" id="helpModal">
        <div class="modal-content">
            <span class="close" onclick="closeHelpModal()">&times;</span>
            <h3>Hilfe zur Frage</h3>
            <div id="helpContent"></div>
        </div>
    </div>

    <script src="assets/js/diagnose.js"></script>
</body>
</html>
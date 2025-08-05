<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - Interaktive Diagnose</title>
    <link rel="stylesheet" href="assets/css/interactive-diagnose.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Interaktive Diagnose</h1>
            <p>Detaillierte Fehleranalyse mit Schritt-für-Schritt Anleitung</p>
        </header>

        <div class="diagnosis-workspace">
            <div class="sidebar">
                <h3>Diagnose-Assistent</h3>
                <div class="diagnosis-steps">
                    <div class="step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-text">Fehlercodes auslesen</span>
                    </div>
                    <div class="step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-text">Komponenten prüfen</span>
                    </div>
                    <div class="step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-text">Lösung finden</span>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <div class="content-step active" data-step="1">
                    <h2>Fehlercodes auslesen</h2>
                    <div class="code-reader">
                        <div class="obd-interface">
                            <div class="connection-status">
                                <span class="status-indicator"></span>
                                <span>OBD-II Adapter verbunden</span>
                            </div>
                            <button class="read-codes-btn" onclick="readErrorCodes()">
                                Fehlercodes auslesen
                            </button>
                        </div>
                        <div class="codes-display" id="codes-display">
                            <p>Keine Fehlercodes gefunden</p>
                        </div>
                    </div>
                </div>

                <div class="content-step" data-step="2">
                    <h2>Komponenten prüfen</h2>
                    <div class="component-checker">
                        <div class="component-list">
                            <div class="component-item" data-component="zuspule">
                                <span>Zündspule</span>
                                <button onclick="checkComponent('zuspule')">Prüfen</button>
                            </div>
                            <div class="component-item" data-component="kerzen">
                                <span>Zündkerzen</span>
                                <button onclick="checkComponent('kerzen')">Prüfen</button>
                            </div>
                            <div class="component-item" data-component="filter">
                                <span>Kraftstofffilter</span>
                                <button onclick="checkComponent('filter')">Prüfen</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-step" data-step="3">
                    <h2>Lösung finden</h2>
                    <div class="solution-recommendations">
                        <div class="solution-card">
                            <h3>Empfohlene Maßnahmen</h3>
                            <ul class="solution-list">
                                <li>Zündspule Zylinder 3 tauschen</li>
                                <li>Zündkerzen überprüfen und ggf. erneuern</li>
                                <li>Kraftstofffilter wechseln</li>
                            </ul>
                            <div class="cost-estimate">
                                <strong>Geschätzte Kosten:</strong> 180-250 €
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/interactive-diagnose.js"></script>
</body>
</html>
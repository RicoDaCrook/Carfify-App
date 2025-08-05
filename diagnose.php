<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - KI Diagnose</title>
    <link rel="stylesheet" href="assets/css/diagnose.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1a1a2e">
</head>
<body>
    <div class="diagnose-container">
        <header class="diagnose-header">
            <h1>KI Fahrzeugdiagnose</h1>
            <p>Intelligente Analyse deines Fahrzeugs</p>
        </header>

        <div id="diagnose-flow">
            <!-- Schritt 1: Fahrzeugauswahl -->
            <div id="step-vehicle" class="step active">
                <h2>Fahrzeug auswählen</h2>
                <div class="vehicle-grid">
                    <div class="vehicle-card" data-vehicle="vw-golf">
                        <img src="assets/img/vw-golf.png" alt="VW Golf">
                        <h3>VW Golf</h3>
                        <p>2020 • 45.000 km</p>
                    </div>
                    <div class="vehicle-card" data-vehicle="bmw-3er">
                        <img src="assets/img/bmw-3er.png" alt="BMW 3er">
                        <h3>BMW 3er</h3>
                        <p>2019 • 67.000 km</p>
                    </div>
                </div>
            </div>

            <!-- Schritt 2: Symptome -->
            <div id="step-symptoms" class="step">
                <h2>Symptome beschreiben</h2>
                <div class="symptoms-grid">
                    <button class="symptom-btn" data-symptom="motor-geraeusche">
                        <span class="icon">🔊</span>
                        <span>Motorgeräusche</span>
                    </button>
                    <button class="symptom-btn" data-symptom="startprobleme">
                        <span class="icon">🔋</span>
                        <span>Startprobleme</span>
                    </button>
                    <button class="symptom-btn" data-symptom="leistungsverlust">
                        <span class="icon">📉</span>
                        <span>Leistungsverlust</span>
                    </button>
                    <button class="symptom-btn" data-symptom="warnleuchten">
                        <span class="icon">⚠️</span>
                        <span>Warnleuchten</span>
                    </button>
                </div>
            </div>

            <!-- Schritt 3: Analyse -->
            <div id="step-analysis" class="step">
                <div class="analysis-container">
                    <div class="loading-animation">
                        <div class="ai-brain">
                            <div class="brain-pulse"></div>
                            <div class="neural-nodes">
                                <div class="node"></div>
                                <div class="node"></div>
                                <div class="node"></div>
                                <div class="node"></div>
                            </div>
                        </div>
                        <h2>KI analysiert...</h2>
                        <div class="loading-text">
                            <span class="dot">.</span>
                            <span class="dot">.</span>
                            <span class="dot">.</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schritt 4: Ergebnis -->
            <div id="step-result" class="step">
                <div class="result-card">
                    <div class="confidence-indicator">
                        <div class="confidence-circle">
                            <svg class="progress-ring" width="120" height="120">
                                <circle
                                    class="progress-ring__circle"
                                    stroke="#e0e0e0"
                                    stroke-width="8"
                                    fill="transparent"
                                    r="52"
                                    cx="60"
                                    cy="60"/>
                                <circle
                                    class="progress-ring__progress"
                                    stroke="url(#gradient)"
                                    stroke-width="8"
                                    fill="transparent"
                                    r="52"
                                    cx="60"
                                    cy="60"/>
                                <defs>
                                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#ff4444"/>
                                        <stop offset="50%" style="stop-color:#ffaa00"/>
                                        <stop offset="100%" style="stop-color:#00ff88"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="confidence-text">
                                <span class="percentage">0%</span>
                                <span class="label">Sicherheit</span>
                            </div>
                        </div>
                    </div>

                    <div class="diagnosis-details">
                        <h3>Mögliche Diagnose</h3>
                        <div class="diagnosis-item">
                            <span class="diagnosis-name">Defekte Zündspule</span>
                            <span class="diagnosis-probability">85%</span>
                        </div>
                        <div class="diagnosis-item">
                            <span class="diagnosis-name">Verschleißte Zündkerzen</span>
                            <span class="diagnosis-probability">72%</span>
                        </div>
                        <div class="diagnosis-item">
                            <span class="diagnosis-name">Kraftstofffilter verstopft</span>
                            <span class="diagnosis-probability">45%</span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="goToInteractiveDiagnosis()">
                            Zur interaktiven Diagnose
                        </button>
                        <button class="btn btn-secondary" onclick="restartDiagnosis()">
                            Neue Diagnose
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/diagnose.js"></script>
</body>
</html>
<?php
require_once 'config/init.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - Die App für jeden Autobesitzer</title>
    <meta name="description" content="KI-gestützte Autodiagnose und intelligente Werkstattsuche. Jetzt auch mit Fahrzeugverkaufs-Feature.">
    
    <!-- PWA -->
    <link rel="manifest" href="pwa-manifest.json">
    <meta name="theme-color" content="#2563eb">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/icon-16x16.png">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="nav-container">
                <h1 class="logo">Carfify</h1>
                <button class="nav-toggle" aria-label="Menü">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <h2 class="hero-title">Die App, die jeder Autobesitzer braucht</h2>
                <p class="hero-subtitle">KI-gestützte Diagnose, intelligente Werkstattsuche und jetzt auch Fahrzeugverkauf</p>
                
                <div class="features-grid">
                    <!-- Diagnose Feature -->
                    <div class="feature-card">
                        <div class="feature-icon">🔧</div>
                        <h3>Autodiagnose</h3>
                        <p>Erkenne Probleme mit KI-Unterstützung bevor sie teuer werden</p>
                        <button class="btn btn-primary" onclick="startDiagnosis()">Diagnose starten</button>
                    </div>
                    
                    <!-- Verkaufs Feature -->
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <h3>Fahrzeug verkaufen</h3>
                        <p>KI-basierte Preisschätzung und professioneller Verkaufsprozess</p>
                        <button class="btn btn-secondary" onclick="startSelling()">Verkauf starten</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Diagnose Modal -->
        <div id="diagnosis-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="diagnosis-content">
                    <!-- Dynamisch gefüllt -->
                </div>
            </div>
        </div>

        <!-- Verkaufs Modal -->
        <div id="selling-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="selling-content">
                    <!-- Dynamisch gefüllt -->
                </div>
            </div>
        </div>

        <!-- Floating Chat Button -->
        <button id="chat-button" class="chat-button" title="Hilfe? Frag Meister Müller!">
            <span>💬</span>
        </button>

        <!-- Chat Modal -->
        <div id="chat-modal" class="modal">
            <div class="modal-content chat-modal-content">
                <div class="chat-header">
                    <h3>Meister Müller hilft</h3>
                    <span class="close">&times;</span>
                </div>
                <div id="chat-messages" class="chat-messages">
                    <div class="message bot">
                        <p>Hallo! Ich bin Meister Müller. Wie kann ich Ihnen helfen?</p>
                    </div>
                </div>
                <div class="chat-input">
                    <input type="text" id="chat-input-field" placeholder="Ihre Frage...">
                    <button onclick="sendChatMessage()">Senden</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/diagnosis.js"></script>
    <script src="assets/js/workshops.js"></script>
    <script src="assets/js/selling.js"></script>
    <script src="assets/js/chat.js"></script>
    
    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js');
        }
    </script>
</body>
</html>
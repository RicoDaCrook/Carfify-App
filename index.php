<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - Die App für jeden Autobesitzer</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="manifest" href="pwa-manifest.json">
    <meta name="theme-color" content="#1a1a2e">
</head>
<body>
    <header class="main-header">
        <h1>Carfify</h1>
        <p>Die App, die jeder Autobesitzer braucht</p>
    </header>

    <main class="main-content">
        <!-- Diagnose-Sektion -->
        <section class="feature-section">
            <h2>KFZ-Diagnose & Werkstattsuche</h2>
            <p>Probleme mit Ihrem Auto? Lassen Sie uns helfen!</p>
            <button class="cta-button" onclick="startDiagnosis()">Diagnose starten</button>
        </section>

        <!-- Verkaufs-Sektion -->
        <section class="feature-section">
            <h2>Fahrzeug verkaufen</h2>
            <p>Bestimmen Sie den Wert Ihres Autos und verkaufen Sie es zum besten Preis</p>
            <button class="cta-button" onclick="startSelling()">Jetzt verkaufen</button>
        </section>

        <!-- Diagnose-Modal -->
        <div id="diagnosis-modal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('diagnosis-modal')">&times;</span>
                <div id="diagnosis-content"></div>
            </div>
        </div>

        <!-- Verkaufs-Modal -->
        <div id="selling-modal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('selling-modal')">&times;</span>
                <div id="selling-content"></div>
            </div>
        </div>
    </main>

    <!-- Permanenter Chat-Button -->
    <button id="chat-button" class="chat-button" onclick="toggleChat()">
        <span>💬</span>
    </button>

    <div id="chat-window" class="chat-window hidden">
        <div class="chat-header">
            <h3>Meister Müller</h3>
            <button onclick="toggleChat()">&times;</button>
        </div>
        <div id="chat-messages" class="chat-messages"></div>
        <div class="chat-input">
            <input type="text" id="chat-input-field" placeholder="Ihre Frage...">
            <button onclick="sendChatMessage()">Senden</button>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/diagnosis.js"></script>
    <script src="assets/js/selling.js"></script>
    <script>
        // PWA Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js');
        }
    </script>
</body>
</html>
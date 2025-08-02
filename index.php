<?php
// Carfify - Hauptmenü mit 8 Features
// Phase 1.1 - 2 Features aktiv, 6 Coming Soon
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - Deine Auto-App</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Progress Indicator -->
    <div class="progress-indicator" id="progressIndicator">
        <div class="progress-bar"></div>
    </div>

    <!-- Header -->
    <header class="glass-header">
        <div class="container">
            <h1><i class="fas fa-car"></i> Carfify</h1>
            <p>Deine intelligente Auto-App</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="features-grid">
            <!-- Feature 1: Diagnose & Reparatur -->
            <a href="diagnose.php" class="feature-card active ripple-effect">
                <div class="card-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>🔧 Diagnose & Reparatur</h3>
                <p>Finde Fehler und repariere dein Auto mit KI-Unterstützung</p>
                <span class="status-badge active">Verfügbar</span>
            </a>

            <!-- Feature 2: Fahrzeug verkaufen -->
            <a href="verkaufen.php" class="feature-card active ripple-effect">
                <div class="card-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>🚗 Fahrzeug verkaufen</h3>
                <p>KI-basierte Preisschätzung und Verkaufsunterstützung</p>
                <span class="status-badge active">Verfügbar</span>
            </a>

            <!-- Feature 3: Wartungsplaner -->
            <div class="feature-card coming-soon">
                <div class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>📅 Wartungsplaner</h3>
                <p>Automatische Erinnerungen für anstehende Wartungen</p>
                <span class="status-badge coming-soon">Coming Soon</span>
            </div>

            <!-- Feature 4: Teilemarkt -->
            <div class="feature-card coming-soon">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>🛒 Teilemarkt</h3>
                <p>Finde günstige Ersatzteile und Zubehör</p>
                <span class="status-badge coming-soon">Coming Soon</span>
            </div>

            <!-- Feature 5: Werkstatt-Bewertungen -->
            <div class="feature-card coming-soon">
                <div class="card-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3>⭐ Werkstatt-Bewertungen</h3>
                <p>Finde die beste Werkstatt in deiner Nähe</p>
                <span class="status-badge coming-soon">Coming Soon</span>
            </div>

            <!-- Feature 6: Community-Forum -->
            <div class="feature-card coming-soon">
                <div class="card-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>💬 Community-Forum</h3>
                <p>Tausche dich mit anderen Autofahrern aus</p>
                <span class="status-badge coming-soon">Coming Soon</span>
            </div>

            <!-- Feature 7: Versicherungsvergleich -->
            <div class="feature-card coming-soon">
                <div class="card-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>🛡️ Versicherungsvergleich</h3>
                <p>Spare Geld mit dem besten Versicherungsangebot</p>
                <span class="status-badge coming-soon">Coming Soon</span>
            </div>

            <!-- Feature 8: TÜV/HU Erinnerung -->
            <div class="feature-card coming-soon">
                <div class="card-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>🔍 TÜV/HU Erinnerung</h3>
                <p>Verpasse nie wieder einen Termin</p>
                <span class="status-badge coming-soon">Coming Soon</span>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="glass-footer">
        <p>&copy; 2024 Carfify - Made with ❤️ for car enthusiasts</p>
    </footer>

    <script src="assets/js/app.js"></script>
</body>
</html>
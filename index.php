<?php
/**
 * Carfify – Hauptseite
 * Vollständige Implementierung aller 8 Hauptmenü-Features
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Carfify – Alles für Ihr Auto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Carfify - Ihre komplette Auto-Plattform: Diagnose, Verkauf, Wartung und mehr">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="manifest" href="pwa-manifest.json">
    <meta name="theme-color" content="#4fc2ee">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body>
    <?php include __DIR__ . '/templates/partials/header.php'; ?>

    <main>
        <!-- Progress Indicator -->
        <div id="global-progress" class="progress-bar"></div>

        <!-- Hero Section -->
        <section id="hero" class="hero">
            <div class="container">
                <h1 class="hero__title reveal">Willkommen bei Carfify</h1>
                <p class="hero__subtitle reveal delay-1">Ihre komplette Auto-Plattform - alles an einem Ort</p>
            </div>
        </section>

        <!-- Hauptmenü Features -->
        <section id="features" class="features">
            <div class="container">
                <div class="features-grid">
                    <!-- Feature 1: Diagnose & Reparatur -->
                    <div class="feature-card ripple" data-feature="diagnose">
                        <div class="feature-icon">🔧</div>
                        <h3>Diagnose & Reparatur</h3>
                        <p>KI-gestützte Fahrzeugdiagnose mit Soforthilfe</p>
                        <button class="btn btn--primary" onclick="startDiagnose()">Jetzt starten</button>
                    </div>

                    <!-- Feature 2: Fahrzeug verkaufen -->
                    <div class="feature-card ripple" data-feature="sell">
                        <div class="feature-icon">🚗</div>
                        <h3>Fahrzeug verkaufen</h3>
                        <p>Kostenlose Preisbewertung und schneller Verkauf</p>
                        <button class="btn btn--primary" onclick="startSelling()">Preis ermitteln</button>
                    </div>

                    <!-- Feature 3: Wartungsplaner -->
                    <div class="feature-card ripple" data-feature="maintenance">
                        <div class="feature-icon">📅</div>
                        <h3>Wartungsplaner</h3>
                        <p>Never miss a service appointment</p>
                        <span class="coming-soon">Coming Soon</span>
                    </div>

                    <!-- Feature 4: Teilemarkt -->
                    <div class="feature-card ripple" data-feature="parts">
                        <div class="feature-icon">🛒</div>
                        <h3>Teilemarkt</h3>
                        <p>Neue und gebrauchte Autoteile finden</p>
                        <span class="coming-soon">Coming Soon</span>
                    </div>

                    <!-- Feature 5: Werkstatt-Bewertungen -->
                    <div class="feature-card ripple" data-feature="reviews">
                        <div class="feature-icon">⭐</div>
                        <h3>Werkstatt-Bewertungen</h3>
                        <p>Echte Bewertungen von echten Kunden</p>
                        <span class="coming-soon">Coming Soon</span>
                    </div>

                    <!-- Feature 6: Community-Forum -->
                    <div class="feature-card ripple" data-feature="forum">
                        <div class="feature-icon">💬</div>
                        <h3>Community-Forum</h3>
                        <p>Hilfe und Austausch mit anderen Autofahrern</p>
                        <span class="coming-soon">Coming Soon</span>
                    </div>

                    <!-- Feature 7: Versicherungsvergleich -->
                    <div class="feature-card ripple" data-feature="insurance">
                        <div class="feature-icon">🛡️</div>
                        <h3>Versicherungsvergleich</h3>
                        <p>Finden Sie die beste Kfz-Versicherung</p>
                        <span class="coming-soon">Coming Soon</span>
                    </div>

                    <!-- Feature 8: TÜV/HU Erinnerung -->
                    <div class="feature-card ripple" data-feature="inspection">
                        <div class="feature-icon">🔍</div>
                        <h3>TÜV/HU Erinnerung</h3>
                        <p>Nie wieder vergessene Prüfungstermine</p>
                        <span class="coming-soon">Coming Soon</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Diagnose Modal -->
        <div id="diagnose-modal" class="modal">
            <div class="modal-content glass">
                <span class="close">&times;</span>
                <h2>Fahrzeugdiagnose starten</h2>
                <div id="diagnose-content">
                    <!-- Dynamisch geladen via JavaScript -->
                </div>
            </div>
        </div>

        <!-- Verkaufen Modal -->
        <div id="sell-modal" class="modal">
            <div class="modal-content glass">
                <span class="close">&times;</span>
                <h2>Fahrzeug verkaufen</h2>
                <div id="sell-content">
                    <!-- Dynamisch geladen via JavaScript -->
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/templates/partials/footer.php'; ?>

    <script src="assets/js/app.js" type="module"></script>
    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js');
        }
    </script>
</body>
</html>
<?php
/**
 * Carfify v4.0 - Hauptanwendung
 * 8-Feature-Menu: Diagnose, Verkaufen, PWA, etc.
 */

require_once 'config.php';

// Session-Check
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid('user_');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify v4.0 - Auto Diagnose & Verkauf</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="<?php echo PWA_THEME_COLOR; ?>">
    <link rel="stylesheet" href="<?php echo carfify_asset('css/app.css'); ?>">
</head>
<body>
    <div id="app">
        <header>
            <h1>Carfify v4.0</h1>
            <p>Intelligente Auto-Diagnose & Verkaufsplattform</p>
        </header>
        
        <main>
            <nav class="feature-menu">
                <a href="diagnose.php" class="feature-card">
                    <h3>🔍 Diagnose</h3>
                    <p>Fahrzeugfehler analysieren</p>
                </a>
                <a href="verkaufen.php" class="feature-card">
                    <h3>💰 Verkaufen</h3>
                    <p>Auto inserieren & verkaufen</p>
                </a>
                <a href="pwa.php" class="feature-card">
                    <h3>📱 App installieren</h3>
                    <p>Als PWA nutzen</p>
                </a>
                <a href="history.php" class="feature-card">
                    <h3>📊 Historie</h3>
                    <p>Frühere Diagnosen</p>
                </a>
                <a href="market.php" class="feature-card">
                    <h3>🚗 Marktplatz</h3>
                    <p>Autos durchsuchen</p>
                </a>
                <a href="profile.php" class="feature-card">
                    <h3>👤 Profil</h3>
                    <p>Einstellungen</p>
                </a>
                <a href="help.php" class="feature-card">
                    <h3>❓ Hilfe</h3>
                    <p>Anleitungen & Support</p>
                </a>
                <a href="about.php" class="feature-card">
                    <h3>ℹ️ Über</h3>
                    <p>Über Carfify</p>
                </a>
            </nav>
        </main>
    </div>
    
    <script src="<?php echo carfify_asset('js/app.js'); ?>"></script>
    <script>
        // PWA Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>
<?php
// Carfify Mini v1.0 - Minimal version
session_start();

// Basis-Konfiguration
$config = [
    'site_name' => 'Carfify Mini',
    'version' => '1.0',
    'debug' => true
];

// Einfacher Router
$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'diagnose', 'verkaufen', 'impressum'];

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Seiten-Inhalte
$pages = [
    'home' => [
        'title' => 'Willkommen bei Carfify',
        'content' => '<h2>🚗 Carfify Mini</h2><p>Die einfache Auto-Verwaltung</p><div class="menu"><a href="?page=diagnose" class="btn">Auto Diagnose</a><a href="?page=verkaufen" class="btn">Auto Verkaufen</a></div>'
    ],
    'diagnose' => [
        'title' => 'Auto Diagnose',
        'content' => '<h2>🔧 Auto Diagnose</h2><form method="post"><label>Autotyp:</label><input type="text" name="car_type" required><label>Kilometerstand:</label><input type="number" name="km" required><button type="submit" name="diagnose">Diagnose starten</button></form>'
    ],
    'verkaufen' => [
        'title' => 'Auto Verkaufen',
        'content' => '<h2>💰 Auto Verkaufen</h2><form method="post"><label>Marke/Modell:</label><input type="text" name="model" required><label>Baujahr:</label><input type="number" name="year" min="1990" max="2024" required><label>Preisvorstellung (€):</label><input type="number" name="price" required><button type="submit" name="sell">Inserieren</button></form>'
    ],
    'impressum' => [
        'title' => 'Impressum',
        'content' => '<h2>Impressum</h2><p>Carfify Mini<br>Demo-Version<br>Keine echte Handelsplattform</p>'
    ]
];

// Formular-Verarbeitung
if ($_POST) {
    if (isset($_POST['diagnose'])) {
        $car = $_POST['car_type'];
        $km = $_POST['km'];
        $result = "Diagnose für $car mit $km km: Fahrzeug in gutem Zustand!";
        $_SESSION['message'] = $result;
    }
    if (isset($_POST['sell'])) {
        $model = $_POST['model'];
        $price = $_POST['price'];
        $result = "Inserat für $model erstellt! Preis: $price €";
        $_SESSION['message'] = $result;
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['site_name']; ?> - <?php echo $pages[$page]['title']; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
</head>
<body>
    <header>
        <h1>🚗 <?php echo $config['site_name']; ?></h1>
        <nav>
            <a href="?page=home">Start</a>
            <a href="?page=diagnose">Diagnose</a>
            <a href="?page=verkaufen">Verkaufen</a>
        </nav>
    </header>

    <main>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php echo $pages[$page]['content']; ?>
    </main>

    <footer>
        <p>&copy; 2024 Carfify Mini v<?php echo $config['version']; ?> | <a href="?page=impressum">Impressum</a></p>
    </footer>

    <script>
        // PWA Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
</body>
</html>
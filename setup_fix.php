<?php
/**
 * Carfify Setup Fix Script
 * Behebt: Composer Dependencies + Ordner-Rechte
 */

echo "🔧 Carfify Setup Fix gestartet...\n\n";

// 1. Composer Dependencies prüfen/installieren
if (!file_exists('vendor/autoload.php')) {
    echo "📦 Composer Autoloader fehlt - installiere Dependencies...\n";
    
    // Prüfe ob Composer verfügbar ist
    $composerAvailable = shell_exec('composer --version');
    if ($composerAvailable) {
        echo shell_exec('composer install --no-dev --optimize-autoloader');
    } else {
        echo "⚠️  Composer nicht im PATH gefunden. Manuelle Installation erforderlich:\n";
        echo "   curl -sS https://getcomposer.org/installer | php\n";
        echo "   php composer.phar install\n\n";
    }
} else {
    echo "✅ Composer Autoloader bereits vorhanden\n";
}

// 2. Ordner-Rechte korrigieren
$folders = ['uploads', 'cache'];
foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
        echo "📁 Ordner $folder erstellt\n";
    }
    
    // Prüfe Schreibrechte
    if (!is_writable($folder)) {
        if (chmod($folder, 0755)) {
            echo "🔓 Schreibrechte für $folder gesetzt (755)\n";
        } else {
            echo "❌ Konnte Schreibrechte für $folder nicht setzen\n";
        }
    } else {
        echo "✅ $folder ist bereits beschreibbar\n";
    }
}

// 3. Health Check
$health = [];
$health['autoload'] = file_exists('vendor/autoload.php');
$health['uploads_writable'] = is_writable('uploads');
$health['cache_writable'] = is_writable('cache');

echo "\n📊 Health Check Ergebnisse:\n";
echo "- Autoloader: " . ($health['autoload'] ? "✅ OK" : "❌ FEHLER") . "\n";
echo "- Uploads: " . ($health['uploads_writable'] ? "✅ OK" : "❌ FEHLER") . "\n";
echo "- Cache: " . ($health['cache_writable'] ? "✅ OK" : "❌ FEHLER") . "\n";

if (array_product($health)) {
    echo "\n🎉 Setup erfolgreich! Die Website sollte jetzt laden.\n";
    echo "   → Teste: http://localhost/index.php\n";
} else {
    echo "\n⚠️  Setup unvollständig. Manuelle Schritte erforderlich.\n";
}

// Optional: .htaccess für Uploads schützen
$htaccessContent = "<Files *>\n  Require all denied\n</Files>\n<Files \"*.jpg|*.jpeg|*.png|*.gif|*.pdf\">\n  Require all granted\n</Files>";

foreach (['uploads', 'cache'] as $folder) {
    $htaccessPath = $folder . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents($htaccessPath, $htaccessContent);
    }
}

echo "\n🔒 Sicherheits-Dateien (.htaccess) erstellt\n";
?>
<?php
// setup.php - Einrichtungsskript

echo "Carfify WebApp Setup\n";
echo "==================\n\n";

// Composer installieren falls nicht vorhanden
if (!file_exists('vendor/autoload.php')) {
    echo "Composer Autoloader wird erstellt...\n";
    exec('composer install', $output, $return);
    if ($return !== 0) {
        echo "Fehler: Composer nicht gefunden. Bitte installieren: https://getcomposer.org/\n";
        exit(1);
    }
    echo "✓ Composer Autoloader erstellt\n";
}

// Rechte prüfen
$dirs = ['config', 'src', 'vendor'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ Verzeichnis {$dir} erstellt\n";
    }
}

echo "\n✓ Setup abgeschlossen!\n";
echo "Starte die App: http://localhost/index.php\n";

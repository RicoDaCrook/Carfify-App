<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config->get('app_name')) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; padding: 10px; background: #e8f5e8; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($config->get('app_name')) ?></h1>
        <div class="success">
            <strong>✓ Erfolg!</strong> Die WebApp läuft korrekt.
        </div>
        <p>Debug-Modus: <?= $config->get('debug') ? 'Aktiv' : 'Inaktiv' ?></p>
        <p>PHP-Version: <?= PHP_VERSION ?></p>
    </div>
</body>
</html>
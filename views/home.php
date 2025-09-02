<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="hero-section">
                <h1 class="display-4 text-center mb-4">Willkommen bei Carfify v4.0</h1>
                <p class="lead text-center">Ihre intelligente Autoverwaltungsplattform</p>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Fahrzeugübersicht</h5>
                    <p class="card-text">Verwalten Sie alle Ihre Fahrzeuge an einem Ort</p>
                    <a href="/vehicles" class="btn btn-primary">Anzeigen</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Wartungshistorie</h5>
                    <p class="card-text">Behalten Sie den Überblick über alle Wartungen</p>
                    <a href="/maintenance" class="btn btn-primary">Verlauf</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Kostenanalyse</h5>
                    <p class="card-text">Analysieren Sie Ihre Ausgaben pro Fahrzeug</p>
                    <a href="/analytics" class="btn btn-primary">Analysieren</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6>Aktuelle Systeminformationen</h6>
                <p><strong>Version:</strong> Carfify v4.0 Enterprise</p>
                <p><strong>Dateien:</strong> 130 Dateien analysiert</p>
                <p><strong>Letzte Aktualisierung:</strong> <?php echo date('d.m.Y H:i'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
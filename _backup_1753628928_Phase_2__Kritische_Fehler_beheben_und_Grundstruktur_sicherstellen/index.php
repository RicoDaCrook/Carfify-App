<?php
/**
 * Carfify – Hauptseite
 * =====================
 * Startpunkt der Anwendung. Bindet Header, Footer und alle Assets ein.
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Carfify – Diagnose & Verkauf</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kostenlose Fahrzeug-Diagnose und sofortiger Ankauf – einfach, sicher und transparent.">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/templates/partials/header.php'; ?>

    <main>
        <!-- Hero / Diagnose starten -->
        <section id="hero" class="section hero">
            <div class="container">
                <h1 class="hero__title reveal">Diagnose starten</h1>
                <p class="hero__subtitle reveal delay-1">Finde in wenigen Schritten den Wert deines Autos heraus.</p>
                <button class="btn btn--primary ripple" data-action="start-diagnose">Jetzt starten</button>
            </div>
        </section>

        <!-- Fahrzeug verkaufen -->
        <section id="sell" class="section sell">
            <div class="container">
                <h2 class="sell__title reveal">Fahrzeug verkaufen</h2>
                <p class="sell__subtitle reveal delay-1">Transparent, sicher und ohne lästige Verhandlungen.</p>
                <div class="sell__cards">
                    <article class="card reveal delay-2">
                        <h3>1. Daten eingeben</h3>
                        <p>Fahrzeugschein & Fotos hochladen – fertig.</p>
                    </article>
                    <article class="card reveal delay-3">
                        <h3>2. Angebot erhalten</h3>
                        <p>Binnen Minuten ein faires Marktpreis-Angebot.</p>
                    </article>
                    <article class="card reveal delay-4">
                        <h3>3. Kostenlos abholen</h3>
                        <p>Wir holen dein Auto deutschlandweit ab – ohne Kosten.</p>
                    </article>
                </div>
                <button class="btn btn--secondary ripple" data-action="sell-vehicle">Jetzt verkaufen</button>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/templates/partials/footer.php'; ?>

    <script src="assets/js/app.js" defer></script>
</body>
</html>
<?php
$pageTitle = 'Fahrzeug übernommen';
$breadcrumbs = ['Fahrzeugauswahl', 'Zusammenfassung'];
require __DIR__ . '/../layout/header.php';
?>
<section class="grid lg:grid-cols-3 gap-6">
    <article class="card p-6 lg:col-span-2 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h2>
                <p class="text-slate-500">Baujahr <?= htmlspecialchars($vehicle['year']) ?> &middot; <?= htmlspecialchars($vehicle['fuel_type']) ?> &middot; <?= htmlspecialchars($vehicle['engine']) ?></p>
            </div>
            <span class="badge bg-emerald-50 text-emerald-600"><i class="fas fa-check-circle"></i> gespeichert</span>
        </header>
        <div class="grid md:grid-cols-2 gap-4 items-start">
            <img src="<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['model']) ?>" class="rounded-2xl object-cover w-full h-56">
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Systeme mit Fokus</h3>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        <?php foreach ($vehicle['systems'] as $system): ?>
                            <li class="badge bg-indigo-50 text-indigo-600"><i class="fas fa-microchip"></i> <?= htmlspecialchars($system) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Highlights</h3>
                    <ul class="mt-2 space-y-2 text-sm text-slate-600">
                        <?php foreach ($vehicle['highlights'] as $highlight): ?>
                            <li><i class="fas fa-sparkles text-indigo-400 mr-2"></i><?= htmlspecialchars($highlight) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <section class="grid md:grid-cols-3 gap-4">
            <?php foreach ($recommendedRoutes as $route): ?>
                <a href="<?= htmlspecialchars($route['url']) ?>" class="card p-4 hover:shadow-xl transition group">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800 group-hover:text-indigo-600 transition"><?= htmlspecialchars($route['title']) ?></h3>
                        <span class="text-indigo-500"><i class="fas <?= htmlspecialchars($route['icon']) ?>"></i></span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed"><?= htmlspecialchars($route['description']) ?></p>
                    <div class="mt-4 text-sm font-semibold text-indigo-500">Weiter <i class="fas fa-arrow-right ml-1"></i></div>
                </a>
            <?php endforeach; ?>
        </section>
    </article>
    <aside class="space-y-6">
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Nächste Schritte</h3>
            <ol class="space-y-3 text-sm text-slate-600">
                <li class="flex items-start gap-3">
                    <span class="badge bg-indigo-100 text-indigo-700">1</span>
                    <div>
                        <strong>Symptome erfassen</strong>
                        <p>Beschreiben Sie Geräusche, Meldungen und Fahrverhalten im Detail.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="badge bg-indigo-100 text-indigo-700">2</span>
                    <div>
                        <strong>KI-Analyse starten</strong>
                        <p>Unsere KI priorisiert Fehlerpfade und schlägt Prüfungen vor.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="badge bg-indigo-100 text-indigo-700">3</span>
                    <div>
                        <strong>Werkstatt auswählen</strong>
                        <p>Vergleichen Sie Partnerbetriebe mit Spezialisierung auf Ihr Fahrzeug.</p>
                    </div>
                </li>
            </ol>
        </div>
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Verknüpfte Daten</h3>
            <ul class="text-sm text-slate-600 space-y-2">
                <li><i class="fas fa-clock text-indigo-400 mr-2"></i> Erfasst: <?= date('d.m.Y H:i', $vehicle['selected_at']) ?></li>
                <li><i class="fas fa-id-card text-indigo-400 mr-2"></i> ID: #<?= str_pad((string)$vehicle['id'], 4, '0', STR_PAD_LEFT) ?></li>
            </ul>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

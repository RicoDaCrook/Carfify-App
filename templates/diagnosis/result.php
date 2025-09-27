<?php
$pageTitle = 'Diagnoseanalyse';
$breadcrumbs = ['Diagnose', 'Auswertung'];
require __DIR__ . '/../layout/header.php';
?>
<section class="grid lg:grid-cols-3 gap-6">
    <article class="card p-6 lg:col-span-2 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800">Analyseergebnis</h2>
                <p class="text-slate-500">Unsere Heuristik hat die Angaben strukturiert und priorisiert.</p>
            </div>
            <span class="badge bg-indigo-50 text-indigo-600"><i class="fas fa-gauge-high"></i> Priorität <?= (int)$analysis['severity'] ?>/5</span>
        </header>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="card p-4 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-500 uppercase">Risikoabschätzung</h3>
                <p class="mt-2 text-lg font-semibold text-slate-800"><?= ucfirst($analysis['riskLevel']) ?></p>
                <p class="text-sm text-slate-500 mt-2">Zeitrahmen: <?= $analysis['timeCritical'] ? 'sofortige Prüfung angeraten' : 'Terminierung innerhalb der nächsten 7 Tage' ?></p>
            </div>
            <div class="card p-4 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-500 uppercase">Symptomkern</h3>
                <p class="mt-2 text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($analysis['symptoms'])) ?></p>
                <?php if (!empty($analysis['recentChanges'])): ?>
                    <p class="mt-3 text-xs text-slate-500">Relevante Änderungen: <?= htmlspecialchars(implode(', ', $analysis['recentChanges'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <section class="card p-5 bg-white border border-slate-100">
            <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2"><i class="fas fa-layer-group text-indigo-500"></i> Wahrscheinlich betroffene Systeme</h3>
            <?php if ($analysis['affectedSystems']): ?>
                <ul class="mt-4 flex flex-wrap gap-2">
                    <?php foreach ($analysis['affectedSystems'] as $system): ?>
                        <li class="badge bg-indigo-100 text-indigo-700"><i class="fas fa-cubes"></i> <?= htmlspecialchars(ucfirst($system)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="mt-4 text-sm text-slate-500">Keine spezifischen Systeme identifiziert. Führen Sie bitte eine KI-Nachbefragung durch.</p>
            <?php endif; ?>
        </section>
        <section class="grid md:grid-cols-2 gap-4">
            <div class="card p-5">
                <h3 class="text-lg font-semibold text-slate-800">Empfohlene Maßnahmen</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <?php foreach ($recommendations as $recommendation): ?>
                        <li><i class="fas fa-arrow-circle-right text-indigo-400 mr-2"></i><?= htmlspecialchars($recommendation) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card p-5">
                <h3 class="text-lg font-semibold text-slate-800">Nächste Fragen</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <?php foreach (array_slice($nextQuestions, 0, 5) as $question): ?>
                        <li>
                            <div class="font-semibold text-slate-700"><?= htmlspecialchars($question['text']) ?></div>
                            <div class="text-xs text-slate-500">Hinweis: <?= htmlspecialchars($question['hint']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </article>
    <aside class="space-y-6">
        <div class="card p-6 space-y-3">
            <h3 class="text-lg font-semibold text-slate-800">Fahrzeugübersicht</h3>
            <?php if ($vehicle): ?>
                <div class="flex gap-3 items-center">
                    <img src="<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['model']) ?>" class="h-16 w-24 object-cover rounded-lg">
                    <div>
                        <div class="font-semibold text-slate-800"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></div>
                        <div class="text-sm text-slate-500"><?= htmlspecialchars($vehicle['engine']) ?></div>
                        <div class="text-xs text-slate-400 mt-1">Erfasst am <?= date('d.m.Y H:i', $vehicle['selected_at'] ?? time()) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Weiterer Verlauf</h3>
            <ul class="space-y-2 text-sm text-slate-600">
                <li><i class="fas fa-robot text-indigo-400 mr-2"></i> <a href="/ki-analyse" class="text-indigo-600 font-semibold">KI-Analyse starten</a></li>
                <li><i class="fas fa-euro-sign text-indigo-400 mr-2"></i> <a href="/preis-kalkulation" class="text-indigo-600 font-semibold">Kostenrahmen berechnen</a></li>
                <li><i class="fas fa-warehouse text-indigo-400 mr-2"></i> <a href="/werkstatt-suche" class="text-indigo-600 font-semibold">Werkstatt mit Spezialisierung wählen</a></li>
            </ul>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

<?php
$pageTitle = 'Werkstätten finden';
$breadcrumbs = ['Werkstätten'];
require __DIR__ . '/../layout/header.php';
?>
<section class="grid lg:grid-cols-3 gap-6">
    <article class="card p-6 lg:col-span-2 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800">Werkstattvergleich</h2>
                <p class="text-slate-500">Gefiltert nach Ihren Kriterien. <?= count($workshops) ?> von <?= count($analytics['availableTypes']) ? array_sum($analytics['availableTypes']) : count($workshops) ?> Betrieben sichtbar.</p>
            </div>
            <span class="badge bg-indigo-50 text-indigo-600"><i class="fas fa-map-location-dot"></i> Live-Filter</span>
        </header>
        <form method="post" action="/werkstatt-suche/filter" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-slate-600">Radius (km)</label>
                <input type="number" name="radius" value="<?= htmlspecialchars($filters['radius']) ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-600">Typ</label>
                <select name="type" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Alle</option>
                    <option value="freie" <?php if ($filters['type'] === 'freie') echo 'selected'; ?>>Freie Werkstatt</option>
                    <option value="marken" <?php if ($filters['type'] === 'marken') echo 'selected'; ?>>Markenbetrieb</option>
                    <option value="spezial" <?php if ($filters['type'] === 'spezial') echo 'selected'; ?>>Spezialist</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-slate-600">Spezialisierung</label>
                <input type="text" name="specialization" value="<?= htmlspecialchars($filters['specialization']) ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. Tesla, Elektronik">
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="px-5 py-3 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition">Filter anwenden</button>
            </div>
        </form>
        <section class="space-y-4">
            <?php if (!$workshops): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-4 rounded-lg">Keine passenden Werkstätten gefunden. Passen Sie die Filter an.</div>
            <?php endif; ?>
            <?php foreach ($workshops as $workshop): ?>
                <article class="card p-5">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="space-y-2">
                            <h3 class="text-xl font-semibold text-slate-800 flex items-center gap-2">
                                <?= htmlspecialchars($workshop['name']) ?>
                                <span class="badge bg-emerald-50 text-emerald-600"><i class="fas fa-star"></i> <?= number_format($workshop['rating'], 1) ?></span>
                            </h3>
                            <p class="text-sm text-slate-500"><?= htmlspecialchars($workshop['address']) ?> &middot; <?= (int)$workshop['distance'] ?> km entfernt</p>
                            <ul class="flex flex-wrap gap-2 text-xs text-slate-600">
                                <?php foreach ($workshop['specializations'] as $specialization): ?>
                                    <li class="badge bg-slate-100 text-slate-600"><i class="fas fa-tools"></i> <?= htmlspecialchars($specialization) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="text-sm text-slate-500">Leistungen: <?= htmlspecialchars(implode(', ', $workshop['services'])) ?></p>
                        </div>
                        <div class="space-y-3 text-sm text-slate-600">
                            <div><i class="fas fa-phone text-indigo-400 mr-2"></i><?= htmlspecialchars($workshop['contact']['phone']) ?></div>
                            <div><i class="fas fa-envelope text-indigo-400 mr-2"></i><?= htmlspecialchars($workshop['contact']['email']) ?></div>
                            <div><i class="fas fa-user-clock text-indigo-400 mr-2"></i><?= htmlspecialchars($workshop['capacity']) ?></div>
                            <a href="/preis-kalkulation" class="inline-flex items-center gap-2 text-indigo-600 font-semibold">Kostenvoranschlag verbinden <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </article>
    <aside class="space-y-6">
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Analyse</h3>
            <p class="text-sm text-slate-500">Verfügbare Betriebe: <?= $analytics['count'] ?></p>
            <?php if ($analytics['averageRating']): ?>
                <p class="text-sm text-slate-500">Ø Bewertung: <?= number_format($analytics['averageRating'], 2) ?> / 5</p>
            <?php endif; ?>
            <ul class="space-y-1 text-sm text-slate-600">
                <?php foreach ($analytics['availableTypes'] as $type => $count): ?>
                    <li><?= htmlspecialchars(ucfirst($type)) ?>: <?= $count ?> Betriebe</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php if ($analysis): ?>
            <div class="card p-6 space-y-3">
                <h3 class="text-lg font-semibold text-slate-800">Empfohlene Spezialisierung</h3>
                <?php if (!empty($analysis['affectedSystems'])): ?>
                    <p class="text-sm text-slate-600">Fokus auf: <?= htmlspecialchars(implode(', ', array_map('ucfirst', $analysis['affectedSystems']))) ?></p>
                <?php else: ?>
                    <p class="text-sm text-slate-600">Symptome noch breit gestreut – generelle Diagnose empfohlen.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </aside>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

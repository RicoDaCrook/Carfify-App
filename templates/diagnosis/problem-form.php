<?php
$pageTitle = 'Symptome beschreiben';
$breadcrumbs = ['Diagnose', 'Symptome'];
require __DIR__ . '/../layout/header.php';
$old = $old ?? [];
$errors = $errors ?? [];
?>
<section class="grid lg:grid-cols-3 gap-6">
    <article class="card p-6 lg:col-span-2 space-y-6">
        <header class="flex flex-col gap-2">
            <h2 class="text-2xl font-semibold text-slate-800">Symptomaufnahme</h2>
            <p class="text-slate-500">Je detaillierter Ihre Beschreibung, desto zielgenauer kann die KI die Fehlerpfade priorisieren.</p>
        </header>
        <form method="post" action="/problem-beschreibung" class="space-y-6">
            <div>
                <label class="text-sm font-medium text-slate-600">Beschreiben Sie das Problem*</label>
                <textarea name="symptoms" rows="5" class="mt-2 w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. Ruckeln beim Beschleunigen zwischen 60-80 km/h..."><?= htmlspecialchars($old['symptoms'] ?? '') ?></textarea>
                <?php if (!empty($errors['symptoms'])): ?>
                    <p class="mt-2 text-sm text-rose-600 flex items-center gap-2"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($errors['symptoms']) ?></p>
                <?php endif; ?>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-slate-600">Priorität (1 = gering, 5 = kritisch)</label>
                    <input type="range" name="severity" min="1" max="5" value="<?= htmlspecialchars($old['severity'] ?? 3) ?>" class="w-full">
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-600">Aktuelle Laufleistung</label>
                    <input type="text" name="mileage" value="<?= htmlspecialchars($old['mileage'] ?? '') ?>" class="mt-2 w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. 82.000">
                    <?php if (!empty($errors['mileage'])): ?>
                        <p class="mt-2 text-sm text-rose-600 flex items-center gap-2"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($errors['mileage']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-slate-600">Nutzungsprofil</label>
                    <select name="usage_pattern" class="mt-2 w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Bitte wählen</option>
                        <option value="stadt" <?php if (($old['usage_pattern'] ?? '') === 'stadt') echo 'selected'; ?>>Überwiegend Stadtverkehr</option>
                        <option value="autobahn" <?php if (($old['usage_pattern'] ?? '') === 'autobahn') echo 'selected'; ?>>Regelmäßig Autobahn</option>
                        <option value="langstrecke" <?php if (($old['usage_pattern'] ?? '') === 'langstrecke') echo 'selected'; ?>>Langstrecken</option>
                        <option value="kurzstrecke" <?php if (($old['usage_pattern'] ?? '') === 'kurzstrecke') echo 'selected'; ?>>Viele Kurzstrecken</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-600">Umgebungsbedingungen</label>
                    <input type="text" name="environment" value="<?= htmlspecialchars($old['environment'] ?? '') ?>" class="mt-2 w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. nach starken Regenfällen">
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-600">Was wurde zuletzt am Fahrzeug verändert?</label>
                <div class="mt-3 grid md:grid-cols-2 gap-2 text-sm text-slate-600">
                    <?php
                    $presetChanges = [
                        'Inspektion / Ölservice',
                        'Software-Update',
                        'Bremsen erneuert',
                        'Batterie getauscht',
                        'Fahrwerk bearbeitet',
                    ];
                    $selectedChanges = $old['recent_changes'] ?? [];
                    foreach ($presetChanges as $change):
                    ?>
                        <label class="flex items-center gap-2 bg-slate-50 rounded-lg px-3 py-2">
                            <input type="checkbox" name="recent_changes[]" value="<?= htmlspecialchars($change) ?>" <?php if (in_array($change, $selectedChanges, true)) echo 'checked'; ?>>
                            <span><?= htmlspecialchars($change) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500">Die Angaben werden verschlüsselt gespeichert und nur für die Diagnose verwendet.</p>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl shadow-md hover:bg-indigo-700 transition">Analyse vorbereiten</button>
            </div>
        </form>
    </article>
    <aside class="space-y-6">
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Aktuelles Fahrzeug</h3>
            <?php if ($vehicle ?? null): ?>
                <div class="flex gap-3 items-center">
                    <img src="<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['model']) ?>" class="h-16 w-24 object-cover rounded-lg">
                    <div>
                        <div class="font-semibold text-slate-800"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></div>
                        <div class="text-sm text-slate-500"><?= htmlspecialchars($vehicle['fuel_type']) ?> &middot; <?= htmlspecialchars($vehicle['engine']) ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-500">Noch kein Fahrzeug gewählt. <a href="/fahrzeug-auswahl" class="text-indigo-600 font-semibold">Jetzt auswählen</a></p>
            <?php endif; ?>
        </div>
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Fragenkatalog</h3>
            <ul class="space-y-2 text-sm text-slate-600 max-h-64 overflow-y-auto pr-2">
                <?php foreach ($questions as $category): ?>
                    <li>
                        <strong class="text-slate-800"><?= htmlspecialchars($category['title']) ?></strong>
                        <ul class="mt-1 space-y-1 text-xs">
                            <?php foreach ($category['questions'] as $question): ?>
                                <li class="flex gap-2">
                                    <span class="text-indigo-400 mt-0.5"><i class="fas fa-comment-medical"></i></span>
                                    <span><?= htmlspecialchars($question['text']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

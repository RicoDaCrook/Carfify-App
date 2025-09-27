<?php
$pageTitle = 'Preisabschätzung';
$breadcrumbs = ['Kosten'];
require __DIR__ . '/../layout/header.php';
$errors = $errors ?? [];
$old = $old ?? [];
?>
<section class="grid lg:grid-cols-3 gap-6">
    <article class="card p-6 lg:col-span-2 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800">Dynamische Kostenschätzung</h2>
                <p class="text-slate-500">Die KI nutzt Erfahrungswerte und Symptomcluster, um einen realistischen Rahmen zu kalkulieren.</p>
            </div>
            <span class="badge bg-emerald-50 text-emerald-600"><i class="fas fa-calculator"></i> aktualisiert</span>
        </header>
        <section class="card p-5 bg-slate-50 border border-slate-100">
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Preisrahmen</h3>
                    <p class="mt-2 text-lg font-semibold text-slate-800"><?= number_format($estimate['range']['min'], 0, ',', '.') ?> € – <?= number_format($estimate['range']['max'], 0, ',', '.') ?> €</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Arbeitsaufwand</h3>
                    <p class="mt-2 text-lg font-semibold text-slate-800"><?= number_format($estimate['laborHours'], 1, ',', '.') ?> Std.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Treffsicherheit</h3>
                    <p class="mt-2 text-lg font-semibold text-slate-800"><?= $estimate['confidence'] ?>%</p>
                </div>
            </div>
            <p class="mt-3 text-sm text-slate-500">Werte basieren auf ähnlichen Fällen in der Region und der Priorität der Symptome.</p>
        </section>
        <section class="grid md:grid-cols-2 gap-4">
            <div class="card p-5">
                <h3 class="text-lg font-semibold text-slate-800">Empfohlene Prüfungen</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><i class="fas fa-wrench text-indigo-400 mr-2"></i>Geführte Fehlersuche nach Herstellervorgaben</li>
                    <li><i class="fas fa-microscope text-indigo-400 mr-2"></i>Systemcheck der betroffenen Baugruppen</li>
                    <li><i class="fas fa-file-medical text-indigo-400 mr-2"></i>Auswertung des Datenlogs inkl. Messfahrten</li>
                </ul>
            </div>
            <div class="card p-5">
                <h3 class="text-lg font-semibold text-slate-800">Kostentreiber</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><i class="fas fa-bolt text-indigo-400 mr-2"></i>Elektronische Komponenten und Sensorik</li>
                    <li><i class="fas fa-snowflake text-indigo-400 mr-2"></i>Thermomanagement (je nach Fahrzeugtyp)</li>
                    <li><i class="fas fa-road text-indigo-400 mr-2"></i>Arbeitszeit für Testfahrten &amp; Kalibrierung</li>
                </ul>
            </div>
        </section>
        <section class="card p-5">
            <h3 class="text-lg font-semibold text-slate-800">Verbindliches Angebot anfragen</h3>
            <form method="post" action="/preis-kalkulation/anfrage" class="mt-3 space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Ansprechpartner*</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <?php if (!empty($errors['name'])): ?><p class="text-sm text-rose-600 mt-1"><?= htmlspecialchars($errors['name']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">E-Mail*</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <?php if (!empty($errors['email'])): ?><p class="text-sm text-rose-600 mt-1"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-600">Telefon</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Wunschtermin</label>
                        <input type="date" name="preferred_date" value="<?= htmlspecialchars($old['preferred_date'] ?? '') ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500">
                        <?php if (!empty($errors['preferred_date'])): ?><p class="text-sm text-rose-600 mt-1"><?= htmlspecialchars($errors['preferred_date']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-600">Zusätzliche Hinweise</label>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. bevorzugte Werkstatt, wichtige Besonderheiten"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl shadow-md hover:bg-indigo-700 transition">Verbindliches Angebot anfordern</button>
            </form>
        </section>
    </article>
    <aside class="space-y-6">
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Fahrzeug &amp; Diagnose</h3>
            <?php if ($vehicle): ?>
                <p class="text-sm text-slate-600"><strong><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></strong><br><?= htmlspecialchars($vehicle['engine']) ?></p>
            <?php endif; ?>
            <?php if ($analysis): ?>
                <p class="text-sm text-slate-500">Symptome: <?= htmlspecialchars(mb_strimwidth($analysis['symptoms'], 0, 120, '…')) ?></p>
            <?php endif; ?>
        </div>
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Nächste Aktion</h3>
            <ul class="space-y-2 text-sm text-slate-600">
                <li><i class="fas fa-warehouse text-indigo-400 mr-2"></i>Passende Werkstatt reservieren</li>
                <li><i class="fas fa-file-signature text-indigo-400 mr-2"></i>Serviceauftrag digital freigeben</li>
                <li><i class="fas fa-mobile-alt text-indigo-400 mr-2"></i>Status-Updates in der App verfolgen</li>
            </ul>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

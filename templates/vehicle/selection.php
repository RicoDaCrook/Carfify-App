<?php
$pageTitle = 'Fahrzeug auswählen';
$breadcrumbs = ['Fahrzeugauswahl'];
require __DIR__ . '/../layout/header.php';
?>
<section class="grid lg:grid-cols-3 gap-6">
    <article class="card p-6 lg:col-span-2 space-y-6">
        <header class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">Fahrzeugdatenbank</h2>
                <p class="text-slate-500">Wählen Sie das Fahrzeug aus, das wir für die Diagnose vorbereiten sollen.</p>
            </div>
            <span class="badge bg-indigo-50 text-indigo-600"><i class="fas fa-database"></i> <?= count($vehicles) ?> Modelle</span>
        </header>
        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg">
                <strong>Hinweis:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="post" action="/fahrzeug-auswahl" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-4">
                <?php foreach ($vehicles as $vehicle): ?>
                    <label class="card p-4 cursor-pointer border-2 transition hover:border-indigo-300 <?php if (!empty($selection) && $selection['id'] === $vehicle['id']) echo 'border-indigo-500'; ?>">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="vehicle_id" value="<?= $vehicle['id'] ?>" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" <?php if (!empty($selection) && $selection['id'] === $vehicle['id']) echo 'checked'; ?>>
                                <div>
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></div>
                                    <div class="text-sm text-slate-500 flex gap-2">
                                        <span><?= htmlspecialchars($vehicle['year']) ?></span>
                                        <span>&middot;</span>
                                        <span><?= htmlspecialchars($vehicle['fuel_type']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <img src="<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['model']) ?>" class="h-16 w-28 object-cover rounded-md">
                        </div>
                        <ul class="mt-4 text-sm text-slate-500 space-y-1">
                            <?php foreach ($vehicle['highlights'] as $highlight): ?>
                                <li><i class="fas fa-check text-emerald-500 mr-2"></i><?= htmlspecialchars($highlight) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500">Die Auswahl wird für die weitere Diagnose gespeichert und kann später angepasst werden.</p>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl shadow-md hover:bg-indigo-700 transition">
                    Fahrzeug übernehmen
                </button>
            </div>
        </form>
    </article>
    <aside class="space-y-6">
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Diagnosefortschritt</h3>
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex items-center gap-2"><span class="h-2 w-2 bg-indigo-500 rounded-full"></span> Fahrzeug wählen</li>
                <li class="flex items-center gap-2 text-slate-400"><span class="h-2 w-2 bg-slate-300 rounded-full"></span> Symptome erfassen</li>
                <li class="flex items-center gap-2 text-slate-400"><span class="h-2 w-2 bg-slate-300 rounded-full"></span> KI-Analyse starten</li>
                <li class="flex items-center gap-2 text-slate-400"><span class="h-2 w-2 bg-slate-300 rounded-full"></span> Werkstätten vergleichen</li>
            </ul>
        </div>
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Import per HSN/TSN</h3>
            <p class="text-sm text-slate-500">Unser Team kann Ihr Fahrzeug auch automatisiert aus der Typklassen-Datenbank importieren. Sprechen Sie uns im Live-Chat an.</p>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

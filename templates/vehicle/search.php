<?php
$pageTitle = 'Fahrzeuge durchsuchen';
$breadcrumbs = ['Fahrzeugdatenbank'];
require __DIR__ . '/../layout/header.php';
?>
<section class="card p-6 space-y-6">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800">Erweiterte Fahrzeugsuche</h2>
            <p class="text-slate-500">Filtern Sie nach Marke, Modell, Antriebsart oder Baujahr. Aktuell <?= $totalVehicles ?> Fahrzeuge hinterlegt.</p>
        </div>
        <span class="badge bg-slate-100 text-slate-600"><i class="fas fa-search"></i> Live-Filter</span>
    </header>
    <form method="get" action="/fahrzeug-suche" class="grid md:grid-cols-5 gap-4">
        <div class="md:col-span-1">
            <label class="text-sm font-medium text-slate-600">Marke</label>
            <input type="text" name="brand" value="<?= htmlspecialchars($filters['brand']) ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. BMW">
        </div>
        <div class="md:col-span-1">
            <label class="text-sm font-medium text-slate-600">Modell</label>
            <input type="text" name="model" value="<?= htmlspecialchars($filters['model']) ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. Golf">
        </div>
        <div class="md:col-span-1">
            <label class="text-sm font-medium text-slate-600">Antrieb</label>
            <input type="text" name="fuel_type" value="<?= htmlspecialchars($filters['fuel_type']) ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="Hybrid, Diesel, ...">
        </div>
        <div class="md:col-span-1">
            <label class="text-sm font-medium text-slate-600">Baujahr</label>
            <input type="number" name="year" value="<?= htmlspecialchars($filters['year']) ?>" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="2023">
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="submit" class="w-full px-4 py-3 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition"><i class="fas fa-filter mr-2"></i>Filtern</button>
        </div>
    </form>
    <div class="grid md:grid-cols-2 gap-4">
        <?php if (!$vehicles): ?>
            <div class="col-span-2 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-4 rounded-lg">
                <strong>Keine Treffer.</strong> Passen Sie die Filter an oder wählen Sie eine andere Marke.
            </div>
        <?php endif; ?>
        <?php foreach ($vehicles as $vehicle): ?>
            <div class="card p-4 flex gap-4">
                <img src="<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['model']) ?>" class="h-28 w-40 object-cover rounded-lg">
                <div class="flex-1 space-y-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h3>
                        <p class="text-sm text-slate-500"><?= htmlspecialchars($vehicle['fuel_type']) ?> &middot; <?= htmlspecialchars($vehicle['power']) ?> &middot; <?= htmlspecialchars($vehicle['transmission']) ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                        <?php foreach ($vehicle['systems'] as $system): ?>
                            <span class="badge bg-slate-100 text-slate-600"><i class="fas fa-atom"></i> <?= htmlspecialchars($system) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <a href="/fahrzeug-auswahl" class="inline-flex items-center gap-2 text-indigo-600 font-semibold">Dieses Fahrzeug vorbereiten <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

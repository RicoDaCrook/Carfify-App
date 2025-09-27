<?php
$pageTitle = 'Anfrage bestätigt';
$breadcrumbs = ['Kosten', 'Bestätigung'];
require __DIR__ . '/../layout/header.php';
?>
<section class="card p-6 space-y-6">
    <header class="flex items-center gap-3">
        <span class="text-emerald-500 text-3xl"><i class="fas fa-circle-check"></i></span>
        <div>
            <h2 class="text-2xl font-semibold text-slate-800">Anfrage eingegangen</h2>
            <p class="text-slate-500">Wir haben die Details gespeichert und melden uns kurzfristig.</p>
        </div>
    </header>
    <section class="grid md:grid-cols-3 gap-4">
        <div class="card p-5 bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-500 uppercase">Referenz</h3>
            <p class="mt-2 text-lg font-semibold text-slate-800">#<?= htmlspecialchars($reference) ?></p>
        </div>
        <div class="card p-5 bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-500 uppercase">Kontakt</h3>
            <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($request['name']) ?><br><?= htmlspecialchars($request['email']) ?></p>
        </div>
        <div class="card p-5 bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-500 uppercase">Zeitrahmen</h3>
            <p class="mt-2 text-sm text-slate-600">Anfrage vom <?= date('d.m.Y H:i', $request['created_at']) ?><?php if ($request['preferred_date']): ?> · Wunschtermin <?= htmlspecialchars(date('d.m.Y', strtotime($request['preferred_date']))) ?><?php endif; ?></p>
        </div>
    </section>
    <section class="grid md:grid-cols-2 gap-4">
        <div class="card p-5">
            <h3 class="text-lg font-semibold text-slate-800">Kostenrahmen</h3>
            <?php if ($request['estimate']): ?>
                <p class="text-sm text-slate-600"><?= number_format($request['estimate']['range']['min'], 0, ',', '.') ?> € – <?= number_format($request['estimate']['range']['max'], 0, ',', '.') ?> €</p>
            <?php endif; ?>
            <?php if ($request['notes']): ?>
                <p class="mt-2 text-sm text-slate-500">Hinweis: <?= htmlspecialchars($request['notes']) ?></p>
            <?php endif; ?>
        </div>
        <div class="card p-5">
            <h3 class="text-lg font-semibold text-slate-800">Nächste Schritte</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                <li><i class="fas fa-envelope text-indigo-400 mr-2"></i>Bestätigung per E-Mail</li>
                <li><i class="fas fa-clipboard-list text-indigo-400 mr-2"></i>Prüfplan als PDF im Kundenkonto</li>
                <li><i class="fas fa-user-gear text-indigo-400 mr-2"></i>Berater meldet sich für Rückfragen</li>
            </ul>
        </div>
    </section>
    <div class="flex justify-end">
        <a href="/werkstatt-suche" class="px-5 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">Werkstätten vergleichen</a>
    </div>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>

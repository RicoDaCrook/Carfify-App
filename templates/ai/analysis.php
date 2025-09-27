<?php
$pageTitle = 'KI-Analyse';
$breadcrumbs = ['KI-Analyse'];
require __DIR__ . '/../layout/header.php';
?>
<section class="grid lg:grid-cols-3 gap-6">
    <article class="card p-6 lg:col-span-2 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800">Intelligente Diagnoseauswertung</h2>
                <p class="text-slate-500">Unsere KI fasst die bisherigen Eingaben zusammen und erstellt eine Hypothese.</p>
            </div>
            <span class="badge bg-indigo-50 text-indigo-600"><i class="fas fa-bolt"></i> Live-Analyse</span>
        </header>
        <?php if (isset($insights['message'])): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-4 rounded-xl">
                <i class="fas fa-info-circle mr-2"></i><?= htmlspecialchars($insights['message']) ?>
            </div>
        <?php else: ?>
            <section class="grid md:grid-cols-2 gap-4">
                <div class="card p-5">
                    <h3 class="text-lg font-semibold text-slate-800">Fahrzeugzusammenfassung</h3>
                    <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($insights['vehicleSummary']) ?></p>
                    <p class="mt-3 text-sm text-slate-500">Risikostatus: <span class="font-semibold text-indigo-600"><?= htmlspecialchars($insights['riskLevel']) ?></span></p>
                </div>
                <div class="card p-5">
                    <h3 class="text-lg font-semibold text-slate-800">Wahrscheinliche Ursachen</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <?php foreach ($insights['probableCauses'] as $cause): ?>
                            <li class="border border-slate-100 rounded-lg px-3 py-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-700"><?= htmlspecialchars($cause['title']) ?></span>
                                    <span class="badge bg-indigo-100 text-indigo-700"><i class="fas fa-percentage"></i> <?= (int)$cause['probability'] ?>%</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($cause['description']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
            <section class="card p-5">
                <h3 class="text-lg font-semibold text-slate-800">Empfohlene Aktionen</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <?php foreach ($insights['nextActions'] as $action): ?>
                        <li><i class="fas fa-arrow-circle-right text-indigo-400 mr-2"></i><?= htmlspecialchars($action) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
        <section class="card p-5">
            <h3 class="text-lg font-semibold text-slate-800">KI-Konversation</h3>
            <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                <?php if (empty($conversation)): ?>
                    <p class="text-sm text-slate-500">Noch keine Rückfragen gestellt. Starten Sie unten eine neue Interaktion.</p>
                <?php endif; ?>
                <?php foreach ($conversation as $entry): ?>
                    <div class="border border-slate-100 rounded-lg px-3 py-2">
                        <div class="text-xs text-slate-400"><?= date('d.m.Y H:i', $entry['timestamp']) ?></div>
                        <div class="text-sm font-semibold text-slate-700 mt-1">Frage: <?= htmlspecialchars($entry['question']) ?></div>
                        <div class="text-sm text-slate-600">Antwort: <?= htmlspecialchars($entry['answer']) ?></div>
                        <div class="text-xs text-indigo-500 mt-1">KI-Folgefrage: <?= htmlspecialchars($entry['follow_up']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form id="ai-interaction" class="mt-4 space-y-3">
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-slate-600">KI-Frage</label>
                        <input type="text" name="question" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. Tritt das Problem auch kalt auf?">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">Ihre Antwort</label>
                        <input type="text" name="answer" class="mt-1 w-full rounded-lg border-slate-200 focus:ring-2 focus:ring-indigo-500" placeholder="z. B. Ja, besonders morgens">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">Rückfrage speichern</button>
                <div id="ai-feedback" class="text-sm text-indigo-600"></div>
            </form>
        </section>
    </article>
    <aside class="space-y-6">
        <div class="card p-6 space-y-4">
            <h3 class="text-lg font-semibold text-slate-800">Fortschritt</h3>
            <div id="progress-indicator" class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                <div class="bg-indigo-500 h-full" style="width: <?= $analysis ? '60' : '20' ?>%"></div>
            </div>
            <p class="text-sm text-slate-500">Fortschritt wird automatisch aktualisiert, sobald neue Informationen vorliegen.</p>
        </div>
        <?php if ($timeline): ?>
            <div class="card p-6 space-y-4">
                <h3 class="text-lg font-semibold text-slate-800">Timeline</h3>
                <ul class="space-y-3 text-sm text-slate-600">
                    <?php foreach ($timeline as $event): ?>
                        <li>
                            <div class="font-semibold text-slate-700"><?= htmlspecialchars($event['title']) ?></div>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars($event['time']) ?></div>
                            <div class="text-sm text-slate-500"><?= htmlspecialchars($event['description']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </aside>
</section>
<script>
const form = document.getElementById('ai-interaction');
const feedback = document.getElementById('ai-feedback');
if (form) {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        const response = await fetch('/ki-analyse/interaktiv', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const payload = await response.json();
        if (payload.error) {
            feedback.textContent = payload.error;
            feedback.className = 'text-sm text-rose-600';
            return;
        }
        feedback.textContent = 'Gespeichert. Nächste KI-Frage: ' + payload.next;
        feedback.className = 'text-sm text-emerald-600';
        setTimeout(() => window.location.reload(), 1200);
    });
}
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>

<?php
$pageTitle = $pageTitle ?? 'Carfify Plattform';
$breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .gradient-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #6366f1 100%);
        }
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(30, 58, 138, 0.12);
        }
        .badge {
            border-radius: 9999px;
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .progress-bar {
            height: 6px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.25);
        }
    </style>
</head>
<body class="min-h-screen">
    <header class="gradient-header text-white">
        <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <a href="/" class="inline-flex items-center gap-3 text-white">
                    <span class="bg-white/15 p-3 rounded-2xl">
                        <i class="fas fa-car-burst text-2xl"></i>
                    </span>
                    <div>
                        <h1 class="text-3xl font-bold">Carfify Diagnose Suite</h1>
                        <p class="text-white/80">Ganzheitliche Fahrzeuganalyse &amp; Serviceprozesse</p>
                    </div>
                </a>
            </div>
            <nav class="flex flex-wrap gap-3">
                <a href="/fahrzeug-auswahl" class="badge bg-white/15 hover:bg-white/25 transition"><i class="fas fa-car"></i> Fahrzeug</a>
                <a href="/problem-beschreibung" class="badge bg-white/15 hover:bg-white/25 transition"><i class="fas fa-stethoscope"></i> Symptome</a>
                <a href="/ki-analyse" class="badge bg-white/15 hover:bg-white/25 transition"><i class="fas fa-robot"></i> KI-Analyse</a>
                <a href="/preis-kalkulation" class="badge bg-white/15 hover:bg-white/25 transition"><i class="fas fa-euro-sign"></i> Kosten</a>
                <a href="/werkstatt-suche" class="badge bg-white/15 hover:bg-white/25 transition"><i class="fas fa-warehouse"></i> Werkstätten</a>
            </nav>
        </div>
        <?php if ($breadcrumbs): ?>
            <div class="bg-white/10">
                <div class="max-w-6xl mx-auto px-6 py-2 text-sm flex gap-2 items-center">
                    <a href="/" class="text-white/80 hover:text-white transition">Übersicht</a>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <span class="text-white/50">&rsaquo;</span>
                        <span class="text-white/90"><?= htmlspecialchars($crumb) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </header>
    <main class="max-w-6xl mx-auto px-6 -mt-14 pb-16">

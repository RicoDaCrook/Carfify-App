<?php
// --- Carfify Landing-Page mit integrierter Diagnose ---
declare(strict_types=1);

// ====================================================================
// Vercel gibt uns kein DOCUMENT_ROOT wie klassische Shared-Hoster,
// daher bilden wir uns „on the fly“ eine konsistente Basis-URL.
// ====================================================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl  = "{$protocol}://{$host}";

// Wir arbeiten nur mit absoluten Pfaden ab Wurzel – damit Vercel-API-Rewrites
// (in vercel.json) korrekt greifen.
define('CARFIFY_BASE_URL', $baseUrl);

session_start();

// HTTP-Caching und Security-Header
header('Cache-Control: public, max-age=600, stale-if-error=300');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
?>
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Carfify – Auto-Diagnose in 90 Sekunden</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0ea5e9">

    <!-- Zusätzliche Sicherheit für Vercel-PWA: -->
    <meta name="apple-mobile-web-app-title" content="Carfify">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <!-- SEO & Social Tags -->
    <meta name="description" content="Erkenne Auto-Probleme sofort – mit KI-gestützter Diagnose und preiswerten Lösungen.">
    <meta property="og:title" content="Carfify – Deine Auto-Diagnose">
    <meta property="og:description" content="Probleme selbst lösen oder günstig zur Werkstatt.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= CARFIFY_BASE_URL ?>">
    <meta property="og:image" content="<?= CARFIFY_BASE_URL ?>/og-image.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Tailwind CDN (caching von unpkg/vercel-optimized CDN empfohlen) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'carfify-primary': '#0ea5e9',
                        'carfify-secondary': '#8b5cf6',
                        'carfify-dark':   '#1e293b',
                        'carfify-light':  '#f8fafc'
                    }
                }
            }
        };
    </script>

    <!-- Inline Critical-CSS -->
    <style>
        .glass {
            background: rgba(255,255,255,.1);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255,255,255,.2);
        }
        .progress-bar {
            height: 4px;
            background: linear-gradient(90deg,#0ea5e9,#8b5cf6);
            transform-origin: left;
            transform: scaleX(0);
            transition: transform .3s ease;
            will-change: transform;
        }
    </style>
</head>
<body class="bg-carfify-light font-sans h-full antialiased">

<!-- =========================== PROGRESS BAR =========================== -->
<div class="fixed top-0 left-0 w-full h-1 bg-slate-300 z-50">
    <div class="progress-bar" id="progressBar"></div>
</div>

<!-- =========================== NAVIGATION ============================ -->
<nav class="fixed w-full bg-white/80 backdrop-blur-md shadow-sm z-40">
    <div class="max-w-7xl mx-auto px-6 flex justify-between items-center h-16">
        <div class="flex items-center">
            <i class="fas fa-car text-carfify-primary text-2xl"></i>
            <span class="ml-2 font-bold text-xl">Carfify</span>
        </div>
        <div class="hidden md:block">
            <a href="#diagnosis"  class="text-sm mx-3 text-gray-700 hover:text-carfify-primary">Diagnose starten</a>
            <a href="#features"  class="text-sm mx-3 text-gray-700 hover:text-carfify-primary">Funktionen</a>
            <a href="#help"      class="text-sm mx-3 text-gray-700 hover:text-carfify-primary">Hilfe</a>
        </div>
    </div>
</nav>

<!-- ============================ CONTENT ============================== -->
<main>
    <!-- Hero -->
    <section class="pt-16 bg-gradient-to-br from-carfify-primary via-purple-600 to-pink-600">
        <div class="max-w-6xl mx-auto px-6 py-24 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold">Dein Auto-Problem in <span class="text-yellow-300">90&nbsp;Sekunden</span> erkannt.</h1>
            <p class="mt-6 mb-8 text-lg max-w-2xl mx-auto text-white/90">
                Mit KI-gestützter Diagnose sofort wissen, was Deinem Auto fehlt und wie Du es günstig behebst – selbst oder in der Werkstatt.
            </p>
            <button onclick="startDiagnosis()" class="group bg-white text-carfify-primary px-8 py-3 rounded-full font-semibold shadow-lg hover:scale-105 transition duration-200">
                <i class="fas fa-play mr-2 group-hover:translate-x-0.5 transition-transform"></i>Jetzt starten
            </button>
        </div>
    </section>

    <!-- Diagnosis-Container (absichtlich leer, wird via JS geleakt) -->
    <section id="diagnosis" class="py-16 bg-gray-50">
        <div class="max-w-3xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-4">Diagnose-Widget wird geladen&nbsp;…</h2>
            <p class="text-center text-gray-600">Falls nach 3 Sekunden nichts passiert:</p>
            <ul class="mt-2 text-sm text-gray-500 list-disc list-inside mx-auto w-max">
                <li>Lade die Seite neu</li>
                <li>Prüfe JavaScript & Dev-Tools-Console</li>
            </ul>
        </div>
    </section>
</main>

<!-- Footer (stylischer Fly-In Loaded footer etc.) -->
<footer class="bg-carfify-dark text-white">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-sm">
            <div>
                <span class="font-bold text-lg inline-flex items-center"><i class="fas fa-car mr-2"></i>Carfify</span>
                <p class="text-gray-400 mt-2">KI-gestützte Auto-Diagnose für alle.</p>
            </div>
            <div>
                <h5 class="font-semibold mb-2">Funktionen</h5>
                <ul class="space-y-1 text-gray-400"><li><a href="#diagnosis">Diagnose starten</a></li></ul>
            </div>
            <div>
                <h5 class="font-semibold mb-2">Support</h5>
                <ul class="space-y-1 text-gray-400"><li><a href="mailto:hello@carfify.app">hello@carfify.app</a></li></ul>
            </div>
            <div>
                <h5 class="font-semibold mb-2">Rechtliches</h5>
                <ul class="space-y-1 text-gray-400"><li><a href="/privacy">Datenschutz</a></li><li><a href="/impressum">Impressum</a></li></ul>
            </div>
        </div>
        <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-500 text-xs">
            &copy; <?= date('Y') ?> Carfify – Alle Rechte vorbehalten.
        </div>
    </div>
</footer>

<!-- ============================ JS =================================== -->
<script src="/assets/js/app.js"     type="module"></script>
<script src="/assets/js/diagnosis.js" type="module"></script>
<script src="/assets/js/animations.js" type="module"></script>

<!-- Inline Utilities (damit wir auch ohne Module arbeiten können) -->
<script>
    // Progress-Bar synchronisieren
    function updateProgress(p = 0) {
        document.getElementById('progressBar').style.transform = `scaleX(${Math.min(Math.max(p, 0), 100) / 100})`;
    }
    window.addEventListener('scroll', () => {
        const st   = document.documentElement.scrollTop;
        const dh   = document.documentElement.scrollHeight - window.innerHeight;
        updateProgress(st / dh * 100);
    });
    function startDiagnosis() {
        document.getElementById('diagnosis')?.scrollIntoView({ behavior: 'smooth' });
        updateProgress(30);
    }
</script>

</body>
</html>

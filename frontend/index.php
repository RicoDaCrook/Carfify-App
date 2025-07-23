<?php
// frontend/index.php
// Carfify Landing-Page mit integrierter Diagnose
declare(strict_types=1);

// Für Vercel: Root-Pfad relativ zu diesem File ermitteln
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__));

// Session starten für Diagnose-Fortschritt
session_start();

// CORS & Security headers (hilfreich auf Vercel)
header('Cache-Control: public, max-age=600, stale-if-error=300');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
?>
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify - Auto-Diagnose in 90 Sekunden</title>
    
    <!-- PWA Manifest (korrekter Pfad auf Vercel) -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0ea5e9">
    
    <!-- SEO & Social -->
    <meta name="description" content="Erkenne Auto-Probleme sofort - mit KI-gestützter Diagnose und preiswerten Lösungen.">
    <meta property="og:title" content="Carfify - Deine Auto-Diagnose">
    <meta property="og:description" content="Probleme selbst lösen oder günstig zur Werkstatt">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://carfify.app">
    <meta property="og:image" content="/og-image.png">
    
    <!-- Styling -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS Custom Build -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'carfify-primary': '#0ea5e9',
                        'carfify-secondary': '#8b5cf6',
                        'carfify-dark': '#1e293b',
                        'carfify-light': '#f8fafc'
                    },
                    backdropBlur: {
                        xs: '2px'
                    }
                }
            }
        }
    </script>
    
    <!-- Inline Critical CSS für schnelles FCP -->
    <style>
        /* Critical CSS für Initial Load */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            -webkit-backdrop-filter: blur(20px); /* Safari */
        }
        
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Progress Bar Styles */
        .progress-bar {
            height: 4px;
            background: linear-gradient(to right, #0ea5e9, #8b5cf6);
            transition: width 0.3s ease;
            transform-origin: left;
            transform: scaleX(0);
        }
        
        /* Vercel optimierte Styles für besseren Mobile Performance */
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body class="bg-carfify-light font-sans h-full">
    <!-- Progress Indicator -->
    <div class="fixed top-0 left-0 w-full h-1 bg-gray-200 z-50">
        <div class="progress-bar" id="progressBar"></div>
    </div>
    
    <!-- Navigation -->
    <nav class="fixed w-full bg-white/80 backdrop-blur-md border-b border-gray-200 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-car text-carfify-primary text-2xl"></i>
                        <span class="ml-2 font-bold text-lg">Carfify</span>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#diagnosis" class="text-gray-700 hover:text-carfify-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            Diagnose starten
                        </a>
                        <a href="#features" class="text-gray-700 hover:text-carfify-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            Funktionen
                        </a>
                        <a href="#help" class="text-gray-700 hover:text-carfify-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            Hilfe
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="pt-16 bg-gradient-to-br from-carfify-primary via-carfify-secondary to-purple-600">
        <div class="max-w-7xl mx-auto py-20 px-4 sm:px-6 lg:px-8">
            <!-- Hero Content -->
            <div class="text-center fade-in">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    Dein Auto-Problem in 
                    <span class="text-yellow-300">90 Sekunden</span> erkannt
                </h1>
                <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                    Mit KI-gestützter Diagnose sofort wissen, was Deinem Auto fehlt 
                    und wie Du es günstig behebst - selbst oder in der Werkstatt.
                </p>
                
                <!-- Primary CTA -->
                <button onclick="startDiagnosis()" 
                        class="group bg-white text-carfify-primary px-8 py-4 rounded-full font-semibold text-lg transform hover:scale-105 transition-all duration-200 shadow-lg">
                    <i class="fas fa-play mr-2 group-hover:translate-x-0.5 transition-transform"></i>
                    Jetzt Diagnose starten
                </button>
            </div>
            
            <!-- Hero Illustration -->
            <div class="mt-20 relative">
                <div class="absolute inset-0 bg-gradient-to-r from-carfify-primary/20 to-carfify-secondary/20 blur-3xl"></div>
                <div class="relative glass rounded-3xl p-8 max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-search text-white text-xl"></i>
                            </div>
                            <h3 class="text-white font-semibold mb-2">Problem erkennen</h3>
                            <p class="text-white/70 text-sm">Beschreibe Symptome, ich analysiere</p>
                        </div>
                        <div class="text-center">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-wrench text-white text-xl"></i>
                            </div>
                            <h3 class="text-white font-semibold mb-2">Lösung finden</h3>
                            <p class="text-white/70 text-sm">Selbst, hybrid oder Werkstatt</p>
                        </div>
                        <div class="text-center">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-dollar-sign text-white text-xl"></i>
                            </div>
                            <h3 class="text-white font-semibold mb-2">Kosten sparen</h3>
                            <p class="text-white/70 text-sm">Bis zu 60% gegenüber Werkstatt allein</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Diagnosis Section -->
    <section id="diagnosis" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Auto-Diagnose starten</h2>
                <p class="text-lg text-gray-600">Gib einfach Deine HS-Nummer und TS-Nummer ein</p>
            </div>
            
            <!-- Vehicle Selection -->
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <!-- Progress Steps -->
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-carfify-primary text-white rounded-full flex items-center justify-center text-sm">1</div>
                            <span class="ml-2 text-sm font-medium text-gray-700">Fahrzeug wählen</span>
                        </div>
                        <div class="flex-1 mx-4 h-1 bg-gray-200 rounded-full">
                            <div class="h-1 bg-gray-300 rounded-full" id="stepProgress1"></div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-200 text-gray-400 rounded-full flex items-center justify-center text-sm">2</div>
                            <span class="ml-2 text-sm text-gray-400">Problem beschreiben</span>
                        </div>
                        <div class="flex-1 mx-4 h-1 bg-gray-200 rounded-full">
                            <div class="h-1 bg-gray-300 rounded-full" id="stepProgress2"></div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-200 text-gray-400 rounded-full flex items-center justify-center text-sm">3</div>
                            <span class="ml-2 text-sm text-gray-400">Ergebnis & Lösung</span>
                        </div>
                    </div>
                    
                    <!-- HSN/TSN Input -->
                    <div id="vehicleSelection">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                HS-Nummer (Herstellerschlüssel)
                            </label>
                            <input type="text" 
                                   id="hsn" 
                                   placeholder="1234" 
                                   maxlength="4"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-carfify-primary focus:border-transparent transition-colors"
                                   oninput="filterVehicleData()"
                                   pattern="[0-9]*"
                                   inputmode="numeric">
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                TS-Nummer (Typschlüssel)
                            </label>
                            <input type="text" 
                                   id="tsn" 
                                   placeholder="ABC" 
                                   maxlength="3"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-carfify-primary focus:border-transparent transition-colors"
                                   oninput="filterVehicleData()"
                                   pattern="[A-Za-z0-9]*"
                                   inputmode="text">
                        </div>
                        
                        <!-- Search Results -->
                        <div id="vehicleResults" class="mb-6 hidden">
                            <h4 class="font-semibold text-gray-700 mb-3">Gefundene Fahrzeuge:</h4>
                            <div id="vehicleList" class="space-y-2">
                                <!-- Dynamisch gefüllt -->
                            </div>
                        </div>
                        
                        <button id="continueBtn" 
                                onclick="nextToProblem()" 
                                disabled
                                class="w-full bg-gray-300 text-gray-500 py-3 rounded-lg font-semibold transition-colors cursor-not-allowed opacity-50"
                                style="display: none;">
                            Weiter zum Problem
                        </button>
                    </div>
                    
                    <!-- Problem Description -->
                    <div id="problemSelection" style="display: none;">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Was macht Dein Fahrzeug?
                            </label>
                            <textarea id="problemDescription" 
                                      rows="4" 
                                      placeholder="z.B. 'Beim Bremsen kommt ein quietschender Laut vom rechten Vorderrad, besonders wenn kalt'..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-carfify-primary focus:border-transparent transition-colors resize-none"
                                      maxlength="500"></textarea>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Beschreibende Symptome (optional)
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <button type="button" class="symptom-tag px-3 py-2 text-sm border border-gray-300 rounded-lg hover:border-carfify-primary transition-colors focus:outline-none focus:ring-2 focus:ring-carfify-primary" onclick="selectSymptom(this)">
                                    <i class="fas fa-thermometer-half mr-1"></i>Kaltstart
                                </button>
                                <button type="button" class="symptom-tag px-3 py-2 text-sm border border-gray-300 rounded-lg hover:border-carfify-primary transition-colors focus:outline-none focus:ring-2 focus:ring-carfify-primary" onclick="selectSymptom(this)">
                                    <i class="fas fa-volume-up mr-1"></i>Geräusche
                                </button>
                                <button type="button" class="symptom-tag px-3 py-2 text-sm border border-gray-300 rounded-lg hover:border-carfify-primary transition-colors focus:outline-none focus:ring-2 focus:ring-carfify-primary" onclick="selectSymptom(this)">
                                    <i class="fas fa-gas-pump mr-1"></i>Verbrauch
                                </button>
                                <button type="button" class="symptom-tag px-3 py-2 text-sm border border-gray-300 rounded-lg hover:border-carfify-primary transition-colors focus:outline-none focus:ring-2 focus:ring-carfify-primary" onclick="selectSymptom(this)">
                                    <i class="fas fa-lightbulb mr-1"></i>Warnlichter
                                </button>
                            </div>
                        </div>
                        
                        <button type="button" onclick="startDiagnosisAI()" 
                                class="w-full bg-carfify-primary hover:bg-carfify-secondary text-white py-3 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-brain mr-2"></i>Jetzt analysieren
                        </button>
                    </div>
                    
                    <!-- Loading State -->
                    <div id="loadingState" style="display: none;" class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                            <i class="fas fa-cog fa-spin text-carfify-primary text-xl"></i>
                        </div>
                        <p class="text-lg text-gray-700 mb-2">Analyse läuft...</p>
                        <p class="text-sm text-gray-500">Untersuche Dein Fahrzeug anhand von millionen Datensätzen</p>
                        <div class="mt-4">
                            <div class="loading-skeleton h-2 w-32 mx-auto rounded"></div>
                            <div class="loading-skeleton h-2 w-24 mx-auto mt-2 rounded opacity-50"></div>
                        </div>
                    </div>
                    
                    <!-- Results -->
                    <div id="results" style="display: none;">
                        <div id="diagnosisResults">
                            <!-- Dynamisch eingefügt -->
                        </div>
                        
                        <button type="button" onclick="newDiagnosis()" 
                                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-refresh mr-2"></i>Neue Diagnose
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">So funktioniert Carfify</h2>
                <p class="text-lg text-gray-600">Von der Typbestimmung bis zur preisgünstigen Reparatur</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1: Easy Input -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-carfify-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-magnifying-glass text-carfify-primary text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">1. Fahrzeug & Problem</h3>
                    <p class="text-gray-600">HSN/TSN eingeben, Problem beschreiben - unsere KI findet passende Lösungen basierend auf Millionen Testdaten.</p>
                </div>
                
                <!-- Feature 2: AI Analysis -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-carfify-secondary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-microchip text-carfify-secondary text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">2. KI-Analyse</h3>
                    <p class="text-gray-600">Unsere KI vergleicht Deine Symptome mit realen Werkstatt-Fällen und identifiziert die wahrscheinlichsten Ursachen.</p>
                </div>
                
                <!-- Feature 3: Cost Solutions -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-piggy-bank text-emerald-500 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">3. Günstige Lösung</h3>
                    <p class="text-gray-600">Erhalte 3 Preisoptionen: Selbstreparatur, Hybrid (Teile kauf + Werkstatt-Einbau) oder Werkstatt-Komplettpaket.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Help Section -->
    <section id="help" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Häufige Fragen</h2>
            </div>
            
            <div class="max-w-3xl mx-auto">
                <div class="space-y-6">
                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Was sind HSN und TSN?</h3>
                        <p class="text-gray-600">HSN (Herstellerschlüssel) und TSN (Typschlüssel) findest Du im Fahrzeugschein. Sie identifizieren eindeutig Dein Fahrzeugmodell.</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Wie genau ist die Diagnose?</h3>
                        <p class="text-gray-600">Unsere KI hat Zugriff auf 2M+ echte Werkstatt-Fälle. Die Genauigkeit liegt bei ca. 85% für typische Probleme.</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Kostet Carfify etwas?</h3>
                        <p class="text-gray-600">Nein! Die Diagnose ist kostenlos. Wir verdienen nur an Empfehlungen für Werkstätten oder Teile, nie an Deiner Reparatur.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-carfify-dark text-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <i class="fas fa-car text-carfify-primary text-xl"></i>
                        <span class="ml-2 font-bold">Carfify</span>
                    </div>
                    <p class="text-gray-400 text-sm">KI-gestützte Auto-Diagnose für alle.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Funktionen</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#diagnosis" class="hover:text-white transition-colors">Diagnose starten</a></li>
                        <li><a href="#features" class="hover:text-white transition-colors">Funktionen</a></li>
                        <li><a href="#help" class="hover:text-white transition-colors">Hilfe</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#help" class="hover:text-white transition-colors">Hilfe-Center</a></li>
                        <li><a href="mailto:hello@carfify.app" class="hover:text-white transition-colors">Kontakt</a></li>
                        <li><a href="/api" class="hover:text-white transition-colors">API</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Rechtliches</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="/privacy" class="hover:text-white transition-colors">Datenschutz</a></li>
                        <li><a href="/impressum" class="hover:text-white transition-colors">Impressum</a></li>
                        <li><a href="/terms" class="hover:text-white transition-colors">AGB</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; 2024 Carfify. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts (korrekte Pfade für Vercel) -->
    <script src="/frontend/assets/js/app.js" type="module"></script>
    <script src="/frontend/assets/js/diagnosis.js" type="module"></script>
    <script src="/frontend/assets/js/animations.js" type="module"></script>
    
    <script>
        // Globale Funktionen
        function updateProgress(percent) {
            document.getElementById('progressBar').style.transform = `scaleX(${Math.min(Math.max(percent, 0), 100) / 100})`;
        }
        
        function startDiagnosis() {
            document.getElementById('diagnosis')?.scrollIntoView({ behavior: 'smooth' });
            updateProgress(15);
        }
        
        // Progress tracking
        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY;
            const docHeight = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
            const scrollPercent = Math.round((scrollTop / docHeight) * 100);
            updateProgress(scrollPercent);
        }, { passive: true });
        
        // Smooth scroll für alle internen Links
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(decodeURIComponent(this.getAttribute('href')));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        });
        
        // Vercel Optimierung: Preload critical resources
        const criticalLinks = [
            '/frontend/assets/js/app.js',
            '/frontend/assets/js/diagnosis.js'
        ];
        
        if (document.head && 'preload' in HTMLLinkElement.prototype) {
            criticalLinks.forEach(href => {
                const link = document.createElement('link');
                link.rel = 'modulepreload';
                link.href = href;
                document.head.appendChild(link);
            });
        }
    </script>
</body>
</html>

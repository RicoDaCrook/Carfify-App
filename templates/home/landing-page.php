<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .tile-hover {
            transition: all 0.3s ease;
        }
        .tile-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="container mx-auto px-6">
            <div class="text-center">
                <h1 class="text-5xl font-bold mb-4">
                    <i class="fas fa-car-side mr-3"></i>
                    Carfify
                </h1>
                <p class="text-xl mb-8">KI-gestützte Fahrzeugdiagnose und Werkstattvermittlung</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                    <div class="bg-white/20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?= number_format($stats['diagnosed_vehicles']) ?></div>
                        <div class="text-sm">Diagnosen</div>
                    </div>
                    <div class="bg-white/20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?= $stats['workshops'] ?></div>
                        <div class="text-sm">Werkstätten</div>
                    </div>
                    <div class="bg-white/20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?= $stats['accuracy'] ?>%</div>
                        <div class="text-sm">Genauigkeit</div>
                    </div>
                    <div class="bg-white/20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?= number_format($stats['satisfied_users']) ?></div>
                        <div class="text-sm">zufriedene Nutzer</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Tiles -->
    <section class="py-16">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">Unsere Services</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($features as $feature): ?>
                    <a href="<?= $feature['url'] ?>" class="tile-hover">
                        <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                            <div class="w-16 h-16 bg-<?= $feature['color'] ?>-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-<?= str_replace('-icon', '', $feature['icon']) ?> text-2xl text-<?= $feature['color'] ?>-600"></i>
                            </div>
                            <h3 class="text-xl font-semibold mb-2"><?= $feature['title'] ?></h3>
                            <p class="text-gray-600"><?= $feature['description'] ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="bg-gray-100 py-16">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">So funktioniert's</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold">1</span>
                    </div>
                    <h3 class="font-semibold mb-2">Fahrzeug auswählen</h3>
                    <p class="text-gray-600">Wählen Sie Ihr Fahrzeug aus der Datenbank oder geben Sie HSN/TSN ein</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold">2</span>
                    </div>
                    <h3 class="font-semibold mb-2">Problem beschreiben</h3>
                    <p class="text-gray-600">Beschreiben Sie die Symptome Ihres Fahrzeugs</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold">3</span>
                    </div>
                    <h3 class="font-semibold mb-2">KI-Analyse</h3>
                    <p class="text-gray-600">Unsere KI analysiert und gibt Diagnosen mit Wahrscheinlichkeiten</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold">4</span>
                    </div>
                    <h3 class="font-semibold mb-2">Werkstatt finden</h3>
                    <p class="text-gray-600">Finden Sie passende Werkstätten mit Preisvergleich</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; 2025 Carfify - KI-Fahrzeugdiagnose</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling für bessere UX
        document.querySelectorAll('a[href^="/"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                window.location.href = this.getAttribute('href');
            });
        });
    </script>
</body>
</html>
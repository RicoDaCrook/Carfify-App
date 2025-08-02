<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fahrzeugzustand erfassen - Carfify</title>
    <link rel="stylesheet" href="/assets/css/selling.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>Fahrzeug verkaufen</h1>
            <p>Schritt 2 von 3: Zustand erfassen</p>
        </header>

        <form id="conditionForm" class="condition-form">
            <input type="hidden" id="vehicleId" value="<?php echo htmlspecialchars($_GET['vehicle_id'] ?? ''); ?>">
            
            <!-- Allgemeiner Zustand -->
            <section class="form-section">
                <h2>Allgemeiner Zustand</h2>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="condition" value="excellent" required>
                        <span class="option-content">
                            <strong>Sehr gut</strong>
                            <small>Keine sichtbaren Gebrauchsspuren</small>
                        </span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="condition" value="good" required>
                        <span class="option-content">
                            <strong>Gut</strong>
                            <small>Normale Gebrauchsspuren</small>
                        </span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="condition" value="fair" required>
                        <span class="option-content">
                            <strong>Gebraucht</strong>
                            <small>Deutliche Gebrauchsspuren</small>
                        </span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="condition" value="poor" required>
                        <span class="option-content">
                            <strong>Stark gebraucht</strong>
                            <small>Reparaturbedürftig</small>
                        </span>
                    </label>
                </div>
            </section>

            <!-- Foto-Upload -->
            <section class="form-section">
                <h2>Fahrzeugfotos hochladen</h2>
                <p class="section-description">Laden Sie mindestens 4 Fotos hoch: Front, Seite, Heck und Innenraum</p>
                
                <div class="photo-upload-area" id="photoUploadArea">
                    <div class="upload-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <p>Fotos hier ablegen oder klicken zum Auswählen</p>
                        <small>JPEG, PNG - max. 10MB pro Datei</small>
                    </div>
                    <input type="file" id="photoInput" multiple accept="image/*" style="display: none;">
                </div>

                <div class="photo-preview" id="photoPreview">
                    <!-- Vorschau der hochgeladenen Fotos -->
                </div>
            </section>

            <!-- Zusätzliche Informationen -->
            <section class="form-section">
                <h2>Zusätzliche Informationen</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="serviceHistory">Serviceheft vorhanden?</label>
                        <select id="serviceHistory" name="service_history">
                            <option value="">Bitte wählen</option>
                            <option value="full">Lückenlos</option>
                            <option value="partial">Teilweise</option>
                            <option value="none">Kein Serviceheft</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="accidents">Unfälle oder Schäden?</label>
                        <select id="accidents" name="accidents">
                            <option value="">Bitte wählen</option>
                            <option value="none">Keine Unfälle</option>
                            <option value="minor">Kleine Schäden</option>
                            <option value="major">Großer Schaden</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tires">Reifen (Sommer)</label>
                        <select id="tires" name="tires_summer">
                            <option value="">Bitte wählen</option>
                            <option value="new">Neu</option>
                            <option value="good">Gut</option>
                            <option value="worn">Abgefahren</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tiresWinter">Reifen (Winter)</label>
                        <select id="tiresWinter" name="tires_winter">
                            <option value="">Bitte wählen</option>
                            <option value="new">Neu</option>
                            <option value="good">Gut</option>
                            <option value="worn">Abgefahren</option>
                            <option value="none">Nicht vorhanden</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="description">Weitere Beschreibung (optional)</label>
                    <textarea id="description" name="description" rows="4" 
                              placeholder="Zusätzliche Informationen zum Fahrzeugzustand..."></textarea>
                </div>
            </section>

            <!-- Navigation -->
            <div class="form-navigation">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                    Zurück
                </button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    Preis berechnen
                    <span class="loading-spinner" style="display: none;"></span>
                </button>
            </div>
        </form>

        <!-- Fortschrittsanzeige -->
        <div class="progress-bar">
            <div class="progress-step completed">1</div>
            <div class="progress-step active">2</div>
            <div class="progress-step">3</div>
        </div>
    </div>

    <script src="/assets/js/selling.js"></script>
</body>
</html>
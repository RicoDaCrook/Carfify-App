<div class="workshop-finder">
    <div class="tab-navigation">
        <button class="tab-button active" data-tab="self-repair">Selbst reparieren</button>
        <button class="tab-button" data-tab="hybrid">Hybrid-Lösung</button>
        <button class="tab-button" data-tab="workshop">Professionelle Werkstatt</button>
    </div>

    <!-- Selbst reparieren Tab -->
    <div id="self-repair-tab" class="tab-content">
        <div class="repair-guides">
            <h2>Schritt-für-Schritt Anleitungen</h2>
            <div class="guides-grid">
                <!-- Dynamisch geladene Anleitungen -->
            </div>
        </div>
    </div>

    <!-- Hybrid-Lösung Tab -->
    <div id="hybrid-tab" class="tab-content" style="display: none;">
        <div class="hybrid-options">
            <h2>Intelligente Hybrid-Lösungen</h2>
            <div class="hybrid-cards">
                <!-- Dynamisch geladene Optionen -->
            </div>
        </div>
    </div>

    <!-- Werkstatt Tab -->
    <div id="workshop-tab" class="tab-content" style="display: none;">
        <?php if (!$diagnosis_complete): ?>
            <div class="diagnosis-hint">
                <i class="fas fa-info-circle"></i>
                <p>Führen Sie die Diagnose vollständig durch, um spezialisierte Werkstätten für Ihr Problem zu sehen.</p>
            </div>
        <?php endif; ?>

        <div class="filters">
            <div class="filter-group">
                <label>Umkreis:</label>
                <input type="range" id="radius-slider" min="5" max="50" value="10">
                <span id="radius-value">10 km</span>
            </div>
            
            <div class="filter-group">
                <label>Sortieren nach:</label>
                <select id="sort-select">
                    <option value="relevance">Relevanz</option>
                    <option value="distance">Entfernung</option>
                    <option value="rating">Bewertung</option>
                    <option value="reviews">Anzahl Bewertungen</option>
                </select>
            </div>
        </div>

        <div id="workshops-loading" style="display: none;">
            <div class="spinner"></div>
            <p>Werkstätten werden geladen...</p>
        </div>

        <div id="workshops-list" class="workshops-container">
            <!-- Dynamisch geladene Werkstätten -->
        </div>
    </div>
</div>

<!-- Workshop Detail Modal -->
<div id="workshop-modal" class="modal">
    <div class="modal-content">
        <div id="modal-content">
            <!-- Dynamisch geladene Details -->
        </div>
    </div>
</div>

<script>
    // Diagnose-Daten übergeben
    const currentDiagnosis = '<?php echo $diagnosis_type ?? ''; ?>';
    const diagnosisComplete = <?php echo $diagnosis_complete ? 'true' : 'false'; ?>;
    
    if (currentDiagnosis) {
        workshopFinder.setDiagnosis(currentDiagnosis);
    }
</script>
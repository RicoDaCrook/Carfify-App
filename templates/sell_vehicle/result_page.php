<div class="selling-result">
    <h2>Ihr Fahrzeug im Überblick</h2>
    
    <div class="price-estimate">
        <h3>Geschätzter Verkaufspreis</h3>
        <div class="price-range">
            <span class="min-price" id="min-price">--</span>
            <span class="separator">-</span>
            <span class="max-price" id="max-price">--</span>
            <span class="currency">€</span>
        </div>
        <p class="price-note">Basierend auf Marktanalyse und Zustand</p>
    </div>
    
    <div class="checklist">
        <h3>Verkaufs-Checkliste</h3>
        <ul id="checklist-items">
            <!-- Dynamisch generiert -->
        </ul>
    </div>
    
    <div class="actions">
        <button class="btn btn-primary" onclick="generateContract()">Kaufvertrag erstellen</button>
        <button class="btn btn-secondary" onclick="downloadChecklist()">Checkliste downloaden</button>
    </div>
    
    <div class="next-steps">
        <h3>Nächste Schritte</h3>
        <ol>
            <li>Fahrzeug professionell reinigen lassen</li>
            <li>Alle Fahrzeugdokumente sammeln</li>
            <li>Fahrzeugbilder für Inserat erstellen</li>
            <li>Inserat auf Portalen erstellen</li>
            <li>Probefahrten organisieren</li>
        </ol>
    </div>
</div>

<style>
.selling-result {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.price-estimate {
    background: #f3f4f6;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 30px;
}

.price-range {
    font-size: 2.5em;
    font-weight: bold;
    color: #2563eb;
    margin: 10px 0;
}

.checklist {
    margin-bottom: 30px;
}

.checklist ul {
    list-style: none;
    padding: 0;
}

.checklist li {
    padding: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
}

.checklist li.completed {
    text-decoration: line-through;
    color: #666;
}

.checklist input[type="checkbox"] {
    margin-right: 10px;
}

.actions {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}

.next-steps ol {
    padding-left: 20px;
}

.next-steps li {
    margin-bottom: 10px;
}
</style>
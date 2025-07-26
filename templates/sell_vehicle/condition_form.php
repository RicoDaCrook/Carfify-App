<div class="selling-form">
    <h3>Fahrzeugzustand erfassen</h3>
    <form id="condition-form">
        <div class="form-group">
            <label for="vehicle-select">Fahrzeug auswählen:</label>
            <select id="vehicle-select" required>
                <option value="">Bitte wählen...</option>
                <option value="1">VW Golf 2018</option>
                <option value="2">BMW 3er 2020</option>
                <option value="3">Audi A4 2019</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="mileage">Kilometerstand:</label>
            <input type="number" id="mileage" required min="0" max="500000" placeholder="z.B. 75000">
        </div>
        
        <div class="form-group">
            <h4>Zustand des Fahrzeugs:</h4>
            <label><input type="checkbox" id="accident-free"> Unfallfrei</label>
            <label><input type="checkbox" id="service-history"> Scheckheftgepflegt</label>
            <label><input type="checkbox" id="first-owner"> Erstbesitz</label>
            <label><input type="checkbox" id="non-smoker"> Nichtraucherfahrzeug</label>
        </div>
        
        <button type="submit" class="cta-button">Weiter zu den Fotos</button>
    </form>
</div>
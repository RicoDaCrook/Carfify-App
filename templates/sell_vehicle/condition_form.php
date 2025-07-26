<div class="selling-form">
    <h2>Fahrzeugzustand erfassen</h2>
    
    <form id="condition-form">
        <div class="form-group">
            <label for="mileage">Kilometerstand</label>
            <input type="number" id="mileage" name="mileage" required min="0" max="999999">
        </div>
        
        <div class="form-group">
            <label>Zustand</label>
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="accident_free" value="1">
                    Unfallfrei
                </label>
                <label>
                    <input type="checkbox" name="service_history" value="1">
                    Scheckheftgepflegt
                </label>
                <label>
                    <input type="checkbox" name="first_owner" value="1">
                    Erstbesitz
                </label>
                <label>
                    <input type="checkbox" name="non_smoker" value="1">
                    Nichtraucherfahrzeug
                </label>
            </div>
        </div>
        
        <div class="form-group">
            <label>Fahrzeugbilder hochladen</label>
            <div class="upload-area" id="upload-area">
                <p>Ziehen Sie Bilder hierher oder klicken Sie zum Auswählen</p>
                <input type="file" id="image-upload" accept="image/*" multiple style="display: none;">
            </div>
            <div id="image-preview" class="image-preview"></div>
        </div>
        
        <div class="form-group">
            <label for="additional-info">Zusätzliche Informationen</label>
            <textarea id="additional-info" name="additional_info" rows="4" 
                placeholder="z.B. Sonderausstattung, Mängel, letzter Service..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Preis schätzen</button>
    </form>
</div>

<style>
.selling-form {
    max-width: 600px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    font-weight: normal;
    cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
    margin-right: 10px;
}

.upload-area {
    border: 2px dashed #ddd;
    border-radius: 4px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.3s;
}

.upload-area:hover {
    border-color: #2563eb;
}

.upload-area.dragover {
    border-color: #2563eb;
    background-color: #f3f4f6;
}

.image-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    margin-top: 20px;
}

.image-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 4px;
}
</style>
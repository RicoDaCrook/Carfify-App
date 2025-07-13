export default async function handler(request, response) {
    const { lat, lon } = request.query;
    // Liest den API-Schlüssel aus den Vercel-Einstellungen.
    const mapsApiKey = process.env.GOOGLE_MAPS_API_KEY;

    // Schritt 1: Überprüfen, ob der Schlüssel auf dem Server gefunden wurde.
    if (!mapsApiKey) {
        console.error("SERVER FEHLER: GOOGLE_MAPS_API_KEY wurde in den Vercel-Einstellungen nicht gefunden.");
        return response.status(500).json({ error: 'Server-Konfigurationsfehler', message: 'Google Maps API-Schlüssel ist auf dem Server nicht gesetzt.' });
    }

    if (!lat || !lon) {
        return response.status(400).json({ error: 'Fehlende Anfrageparameter', message: 'Latitude und Longitude sind erforderlich.' });
    }

    const nearbySearchUrl = `https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=${lat},${lon}&radius=5000&type=car_repair&language=de&key=${mapsApiKey}`;
    try {
        const searchRes = await fetch(nearbySearchUrl);
        const searchData = await searchRes.json();

        // Schritt 2: Überprüfen, ob die Anfrage an Google erfolgreich war.
        if (searchData.status !== "OK") {
            console.error("GOOGLE MAPS API FEHLER:", searchData);
            return response.status(500).json({ error: `Google API Fehler: ${searchData.status}`, details: searchData.error_message || 'Keine Details von Google erhalten.' });
        }

        const workshopDetailsPromises = searchData.results.slice(0, 6).map(async (place) => {
            const detailsUrl = `https://maps.googleapis.com/maps/api/place/details/json?place_id=${place.place_id}&fields=name,rating,user_ratings_total,reviews,photo,vicinity&language=de&key=${mapsApiKey}`;
            const detailsResponse = await fetch(detailsUrl);
            const detailsData = await detailsResponse.json();
            let photoUrl = 'https://placehold.co/400x400/94a3b8/ffffff?text=Carfify';
            if (detailsData.result.photos && detailsData.result.photos.length > 0) {
                const photoReference = detailsData.result.photos[0].photo_reference;
                photoUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${photoReference}&key=${mapsApiKey}`;
            }
            return { ...detailsData.result, photoUrl };
        });
        const workshopsWithDetails = await Promise.all(workshopDetailsPromises);

        // Schritt 3: Erfolgreiche Antwort senden.
        response.status(200).json(workshopsWithDetails);
    } catch (error) {
        console.error("UNERWARTETER SERVER FEHLER:", error);
        response.status(500).json({ error: 'Interner Serverfehler', details: error.message });
    }
}

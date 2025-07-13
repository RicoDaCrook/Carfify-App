// DIAGNOSE-VERSION: Dieser Code schreibt detaillierte Logs, um den Fehler zu finden.
export default async function handler(request, response) {
    console.log("LOG: /api/workshops function started.");

    const { lat, lon } = request.query;
    const mapsApiKey = process.env.GOOGLE_MAPS_API_KEY;

    // Schritt 1: Überprüfen, ob der API-Schlüssel auf dem Server gefunden wurde.
    if (!mapsApiKey) {
        console.error("SERVER ERROR: GOOGLE_MAPS_API_KEY not found in Vercel environment variables.");
        return response.status(500).json({ message: 'Server-Konfigurationsfehler: Google Maps API-Schlüssel ist auf dem Server nicht gesetzt.' });
    }
    console.log("LOG: Google Maps API Key found on server.");

    if (!lat || !lon) {
        console.error("CLIENT ERROR: Latitude or Longitude missing in request.");
        return response.status(400).json({ message: 'Latitude und Longitude sind erforderlich.' });
    }
    console.log(`LOG: Received coordinates: lat=${lat}, lon=${lon}`);

    const nearbySearchUrl = `https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=${lat},${lon}&radius=5000&type=car_repair&language=de&key=${mapsApiKey}`;
    console.log("LOG: Calling Google Maps API with URL:", nearbySearchUrl);

    try {
        const searchRes = await fetch(nearbySearchUrl);
        console.log(`LOG: Google Maps API response status: ${searchRes.status}`);

        const searchData = await searchRes.json();

        // Schritt 2: Überprüfen, ob die Anfrage an Google erfolgreich war.
        if (searchData.status !== "OK") {
            console.error("GOOGLE MAPS API ERROR:", searchData);
            return response.status(500).json({ message: `Google API Fehler: ${searchData.status}`, details: searchData.error_message || 'Keine Details von Google erhalten.' });
        }
        console.log(`LOG: Found ${searchData.results.length} workshops.`);

        const workshopDetailsPromises = searchData.results.slice(0, 4).map(async (place) => {
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
        console.log("LOG: Successfully fetched all workshop details. Sending response to client.");
        response.status(200).json(workshopsWithDetails);

    } catch (error) {
        console.error("UNEXPECTED SERVER ERROR:", error);
        response.status(500).json({ message: 'Interner Serverfehler beim Abrufen der Werkstätten', details: error.message });
    }
}

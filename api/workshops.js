export default async function handler(request, response) {
    const { lat, lon } = request.query;
    const mapsApiKey = process.env.REACT_APP_GOOGLE_MAPS_API_KEY;
    if (!lat || !lon) { return response.status(400).json({ error: 'Latitude and longitude are required' }); }
    if (!mapsApiKey) { return response.status(500).json({ error: 'Google Maps API key not configured' }); }
    const nearbySearchUrl = `https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=${lat},${lon}&radius=5000&type=car_repair&language=de&key=${mapsApiKey}`;
    try {
        const searchRes = await fetch(nearbySearchUrl);
        const searchData = await searchRes.json();
        if (searchData.status !== "OK") { return response.status(500).json({ error: `Google API Error: ${searchData.status}` }); }
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
        response.status(200).json(workshopsWithDetails);
    } catch (error) {
        response.status(500).json({ error: 'Failed to fetch workshops', details: error.message });
    }
}

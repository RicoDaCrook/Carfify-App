export default async function handler(request, response) {
    // Der Radius wird jetzt aus der Anfrage ausgelesen, mit 5000 als Standardwert
    const { lat, lon, problem, vehicleBrand, radius = 5000 } = request.query;
    const mapsApiKey = process.env.Maps_API_KEY;

    if (!mapsApiKey) {
        console.error("SERVER ERROR: Maps_API_KEY not found in Vercel environment variables.");
        return response.status(500).json({ message: 'Server-Konfigurationsfehler: Google Maps API-Schlüssel ist auf dem Server nicht gesetzt.' });
    }

    if (!lat || !lon) {
        return response.status(400).json({ message: 'Latitude und Longitude sind erforderlich.' });
    }

    console.log(`LOG: Searching workshops for problem: ${problem}, vehicle: ${vehicleBrand}, radius: ${radius}`);

    try {
        // Array für verschiedene Suchanfragen
        const searchQueries = [];

        // 1. Vertragswerkstätten (wenn Marke bekannt)
        if (vehicleBrand && vehicleBrand !== 'Nicht angegeben') {
            searchQueries.push({
                query: `${vehicleBrand} Vertragswerkstatt Autohaus`,
                type: 'dealership',
                radius: parseInt(radius) || 10000 // Größerer Radius für Vertragshändler
            });
        }

        // 2. Werkstattketten
        searchQueries.push({
            query: 'ATU Pitstop Euromaster Vergölst',
            type: 'chain',
            radius: parseInt(radius) || 7000
        });

        // 3. Freie Werkstätten
        searchQueries.push({
            query: 'Autowerkstatt KFZ Werkstatt freie Werkstatt',
            type: 'independent',
            radius: parseInt(radius) || 5000
        });

        // 4. Spezialisierte Werkstätten (wenn relevant)
        if (problem) {
            const problemLower = problem.toLowerCase();
            if (problemLower.includes('getriebe') || problemLower.includes('schaltung')) {
                searchQueries.push({
                    query: 'Getriebe Spezialist Getriebewerkstatt',
                    type: 'specialist_transmission',
                    radius: parseInt(radius) || 15000
                });
            }
            if (problemLower.includes('motor')) {
                searchQueries.push({
                    query: 'Motorinstandsetzung Motor Spezialist',
                    type: 'specialist_engine',
                    radius: parseInt(radius) || 15000
                });
            }
        }

        // Alle Suchanfragen parallel ausführen
        const allResults = await Promise.all(
            searchQueries.map(async (searchConfig) => {
                const url = `https://maps.googleapis.com/maps/api/place/textsearch/json?query=${encodeURIComponent(searchConfig.query)}&location=${lat},${lon}&radius=${searchConfig.radius}&type=car_repair&language=de&key=${mapsApiKey}`;

                const searchRes = await fetch(url);
                const searchData = await searchRes.json();

                if (searchData.status === "OK") {
                    // Füge den Werkstatt-Typ zu jedem Ergebnis hinzu
                    return searchData.results.slice(0, 5).map(place => ({ // Limit auf 5 pro Kategorie für bessere Verteilung
                        ...place,
                        workshopType: searchConfig.type
                    }));
                }
                return [];
            })
        );

        // Ergebnisse zusammenführen und duplikate entfernen
        const allWorkshops = allResults.flat();
        const uniqueWorkshops = Array.from(
            new Map(allWorkshops.map(w => [w.place_id, w])).values()
        );

        // Details für jeden Workshop abrufen
        const workshopDetailsPromises = uniqueWorkshops.map(async (place) => {
            const detailsUrl = `https://maps.googleapis.com/maps/api/place/details/json?place_id=${place.place_id}&fields=name,rating,user_ratings_total,reviews,photos,vicinity,formatted_phone_number,opening_hours,website&language=de&key=${mapsApiKey}`;

            try {
                const detailsResponse = await fetch(detailsUrl);
                const detailsData = await detailsResponse.json();

                let photoUrl = 'https://placehold.co/400x400/94a3b8/ffffff?text=Carfify';
                if (detailsData.result && detailsData.result.photos && detailsData.result.photos.length > 0) {
                    const photoReference = detailsData.result.photos[0].photo_reference;
                    photoUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${photoReference}&key=${mapsApiKey}`;
                }

                // Kategorisiere basierend auf Namen und Typ
                let finalType = place.workshopType;
                const nameLower = detailsData.result.name.toLowerCase();

                // Verfeinere die Kategorisierung basierend auf dem Namen
                if (vehicleBrand && nameLower.includes(vehicleBrand.toLowerCase())) {
                    finalType = 'dealership';
                } else if (nameLower.includes('atu') || nameLower.includes('pitstop') ||
                           nameLower.includes('euromaster') || nameLower.includes('vergölst')) {
                    finalType = 'chain';
                } else if (nameLower.includes('getriebe')) {
                    finalType = 'specialist_transmission';
                } else if (nameLower.includes('motor')) {
                    finalType = 'specialist_engine';
                }

                return {
                    ...detailsData.result,
                    place_id: place.place_id, // Wichtig für unique key
                    photoUrl,
                    workshopType: finalType,
                    phone: detailsData.result.formatted_phone_number || null,
                    website: detailsData.result.website || null,
                    opening_hours: detailsData.result.opening_hours || null
                };
            } catch (error) {
                console.error(`Error fetching details for ${place.name}:`, error);
                return null;
            }
        });

        const workshopsWithDetails = (await Promise.all(workshopDetailsPromises))
            .filter(w => w !== null)
            .sort((a, b) => {
                // Sortierung: Erst nach Typ, dann nach Bewertung
                const typeOrder = {
                    'dealership': 1,
                    'chain': 2,
                    'independent': 3,
                    'specialist_transmission': 4,
                    'specialist_engine': 4
                };

                if (typeOrder[a.workshopType] !== typeOrder[b.workshopType]) {
                    return typeOrder[a.workshopType] - typeOrder[b.workshopType];
                }

                return (b.rating || 0) - (a.rating || 0);
            });

        response.status(200).json({
            workshops: workshopsWithDetails,
            metadata: {
                vehicleBrand: vehicleBrand || 'Nicht angegeben',
                hasVehicleBrand: !!vehicleBrand && vehicleBrand !== 'Nicht angegeben'
            }
        });

    } catch (error) {
        console.error("UNEXPECTED SERVER ERROR:", error);
        response.status(500).json({ message: 'Interner Serverfehler beim Abrufen der Werkstätten', details: error.message });
    }
}

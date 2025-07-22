```javascript
import fetch from 'node-fetch';

const CACHE = new Map();
const CACHE_TTL = 60000; // 60 Sekunden

export default async function handler(request, response) {
  const { lat, lon, problem, vehicleBrand, radius = 5000 } = request.query;
  const mapsApiKey = process.env.GOOGLE_MAPS_API_KEY;

  if (!mapsApiKey) {
    return response.status(500).json({ message: 'Server-Konfigurationsfehler: Google Maps API-Key fehlt.' });
  }

  const latNum = Number(lat);
  const lonNum = Number(lon);
  if (isNaN(latNum) || isNaN(lonNum)) {
    return response.status(400).json({ message: 'Latitude und Longitude als gültige Zahlen erforderlich.' });
  }

  const radNum = Number(radius);
  if (isNaN(radNum) || radNum < 100 || radNum > 50000) {
    return response.status(400).json({ message: 'Ungültiger Radius (100–50000 Meter erlaubt).' });
  }

  const normalizedProblem = (problem || '').toLowerCase().trim();
  const normalizedBrand = (vehicleBrand || '').trim();
  const cacheKey = `${latNum}-${lonNum}-${normalizedProblem}-${normalizedBrand}-${radNum}`;

  if (CACHE.has(cacheKey)) {
    const { ts, data } = CACHE.get(cacheKey);
    if (Date.now() - ts < CACHE_TTL) {
      return response.status(200).json(data);
    }
  }

  const searchQueries = [
    ...(normalizedBrand && normalizedBrand !== 'Nicht angegeben'
      ? [{
          query: `${normalizedBrand} Vertragswerkstatt Autohaus`,
          type: 'dealership',
          priority: 0,
          radius: Math.max(radNum, 10000)
        }]
      : []),
    {
      query: 'ATU Pitstop Euromaster Vergölst',
      type: 'chain',
      priority: 1,
      radius: Math.max(radNum, 7000)
    },
    {
      query: 'Autowerkstatt KFZ Werkstatt freie Werkstatt',
      type: 'independent',
      priority: 2,
      radius: radNum
    },
    ...(normalizedProblem.includes('getriebe') || normalizedProblem.includes('schaltung')
      ? [{
          query: 'Getriebe Spezialist Getriebewerkstatt',
          type: 'specialist_transmission',
          priority: 3,
          radius: Math.max(radNum, 15000)
        }]
      : []),
    ...(normalizedProblem.includes('motor')
      ? [{
          query: 'Motorinstandsetzung Motor Spezialist',
          type: 'specialist_engine',
          priority: 3,
          radius: Math.max(radNum, 15000)
        }]
      : [])
  ];

  const baseUrl = 'https://maps.googleapis.com/maps/api/place/textsearch/json';

  let results;
  try {
    const raw = await Promise.allSettled(
      searchQueries.map(({ query, radius }) => {
        const url = new URL(baseUrl);
        url.searchParams.set('query', query);
        url.searchParams.set('location', `${latNum},${lonNum}`);
        url.searchParams.set('radius', radius);
        url.searchParams.set('type', 'car_repair');
        url.searchParams.set('language', 'de');
        url.searchParams.set('key', mapsApiKey);
        return fetch(url).then(r => r.json());
      })
    );

    const seen = new Set();
    results = raw
      .flatMap((p, idx) =>
        (p.status === 'fulfilled' && p.value.status === 'OK')
          ? p.value.results.slice(0, 5).map(r => ({
              ...r,
              workshopType: searchQueries[idx].type,
              priority: searchQueries[idx].priority
            }))
          : []
      )
      .filter(r => {
        if (seen.has(r.place_id)) return false;
        seen.add(r.place_id);
        return true;
      })
      .sort((a, b) => a.priority - b.priority);
  } catch (err) {
    return response.status(502).json({ message: 'Fehler beim Anfragen der Google-Places-API.' });
  }

  const detailsPromises = results.map(async (place) => {
    const detailsUrl = new URL('https://maps.googleapis.com/maps/api/place/details/json');
    detailsUrl.searchParams.set('place_id', place.place_id);
    detailsUrl.searchParams.set(
      'fields',
      'name,rating,user_ratings_total,reviews,photos,vicinity,formatted_phone_number,opening_hours,website'
    );
    detailsUrl.searchParams.set('language', 'de');
    detailsUrl.searchParams.set('key', mapsApiKey);

    try {
      const res = await fetch(detailsUrl);
      const json = await res.json();
      const data = json.result || {};
      const name = data.name?.toLowerCase() || '';

      let type = place.workshopType;
      if (normalizedBrand && name.includes(normalizedBrand.toLowerCase())) {
        type = 'dealership';
      } else if (/atu|pitstop|euromaster|vergölst/.test(name)) {
        type = 'chain';
      } else if (/getriebe/.test(name)) {
        type = 'specialist_transmission';
      } else if (/motor/.test(name)) {
        type = 'specialist_engine';
      }

      let photoUrl = 'https://placehold.co/400x400/94a3b8/ffffff?text=Carfify';
      if (data.photos?.[0]?.photo_reference) {
        photoUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${data.photos[0].photo_reference}&key=${mapsApiKey}`;
      }

      return {
        ...place,
        name: data.name || place.name,
        rating: data.rating || 0,
        user_ratings_total: data.user_ratings_total || 0,
        vicinity: data.vicinity || place.formatted_address || '',
        opening_hours: data.opening_hours || null,
        phone: data.formatted_phone_number || null,
        website: data.website || null,
        photoUrl,
        workshopType: type
      };
    } catch {
      return null;
    }
  });

  let workshops = (await Promise.all(detailsPromises)).filter(Boolean);

  const typeOrder = { dealership: 0, chain: 1, independent: 2, specialist_transmission: 3, specialist_engine: 3 };
  workshops.sort((a, b) => {
    const diffType = typeOrder[a.workshopType] - typeOrder[b.workshopType];
    if (diffType !== 0) return diffType;
    return (b.rating || 0) - (a.rating || 0);
  });

  const payload = {
    workshops,
    metadata: {
      vehicleBrand: normalizedBrand || 'Nicht angegeben',
      hasVehicleBrand: !!normalizedBrand && normalizedBrand !== 'Nicht angegeben'
    }
  };

  CACHE.set(cacheKey, { ts: Date.now(), data: payload });
  setTimeout(() => CACHE.delete(cacheKey), CACHE_TTL);

  response.status(200).json(payload);
}
```
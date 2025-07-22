```javascript
/* api/workshops.js */
import fetch from 'node-fetch';

const CACHE  = new Map();
const TTL_MS = 60_000;

const VALID_TYPES = Object.freeze({
  dealership:            0,
  chain:                 1,
  independent:           2,
  specialist_transmission: 3,
  specialist_engine:     3,
});

export default async function handler(req, res) {
  const mapsKey = process.env.GOOGLE_MAPS_API_KEY;
  if (!mapsKey) {
    return res.status(500).json({ message: 'Server-Konfigurationsfehler: Google-Maps-API-Key fehlt.' });
  }

  const { lat, lon, problem = '', vehicleBrand = '', radius = 5000 } = req.query;

  const latNum = Number(lat);
  const lonNum = Number(lon);
  const radNum = Number(radius);

  if (Number.isNaN(latNum) || Number.isNaN(lonNum)) {
    return res.status(400).json({ message: 'Latitude und Longitude müssen gültige Zahlen sein.' });
  }
  if (!Number.isFinite(radNum) || radNum < 100 || radNum > 50_000) {
    return res.status(400).json({ message: 'Radius muss zwischen 100 und 50.000 Metern liegen.' });
  }

  const normProblem = problem.toLowerCase().trim().replace(/\s+/g, ' ');
  const normBrand   = vehicleBrand.trim();
  const cacheKey    = `${latNum}|${lonNum}|${normProblem}|${normBrand}|${radNum}`;

  const cached = CACHE.get(cacheKey);
  if (cached && Date.now() - cached.ts < TTL_MS) {
    return res.status(200).json(cached.data);
  }

  /* ----- QUERIES ----- */
  const queries = [];

  if (normBrand.length && normBrand !== 'Nicht angegeben') {
    queries.push({
      q: `${normBrand} Vertragswerkstatt Autohaus`,
      type: 'dealership',
      radius: Math.max(radNum, 10_000),
    });
  }

  queries.push(
    {
      q: 'ATU Pitstop Euromaster Vergölst',
      type: 'chain',
      radius: Math.max(radNum, 7_000),
    },
    {
      q: 'Autowerkstatt KFZ freie Werkstatt',
      type: 'independent',
      radius: radNum,
    }
  );

  if (normProblem.includes('getriebe') || normProblem.includes('schaltung')) {
    queries.push({
      q: 'Getriebe Spezialist Getriebewerkstatt',
      type: 'specialist_transmission',
      radius: Math.max(radNum, 15_000),
    });
  }

  if (normProblem.includes('motor')) {
    queries.push({
      q: 'Motorinstandsetzung Motor Spezialist',
      type: 'specialist_engine',
      radius: Math.max(radNum, 15_000),
    });
  }

  /* ----- PLACE SEARCH ----- */
  const ps = new URLSearchParams();
  ps.set('language', 'de');
  ps.set('type', 'car_repair');
  ps.set('key', mapsKey);

  const searchUrls = queries.map(({ q, radius: r }) => {
    const u = new URL('https://maps.googleapis.com/maps/api/place/textsearch/json');
    ps.set('query', q);
    ps.set('location', `${latNum},${lonNum}`);
    ps.set('radius', r);
    u.search = ps.toString();
    return u.toString();
  });

  const searchResults = await Promise.allSettled(
    searchUrls.map((url) => fetch(url).then((r) => r.json()))
  );

  const seen = new Set();
  const places = searchResults
    .flatMap((r, i) => {
      if (r.status !== 'fulfilled' || r.value.status !== 'OK') return [];
      return r.value.results.slice(0, 5).map((p) => ({
        ...p,
        _workshopType: queries[i].type,
      }));
    })
    .filter((p) => {
      const id = p.place_id;
      if (seen.has(id)) return false;
      seen.add(id);
      return true;
    });

  /* ----- PLACE DETAILS ----- */
  const DETAIL_FIELDS =
    'name,rating,user_ratings_total,reviews,photos,vicinity,formatted_phone_number,opening_hours,website';
  const detailPs = new URLSearchParams({ fields: DETAIL_FIELDS, key: mapsKey, language: 'de' });

  const detailsPromises = places.map(async (place) => {
    const url = new URL('https://maps.googleapis.com/maps/api/place/details/json');
    detailPs.set('place_id', place.place_id);
    url.search = detailPs;

    let res;
    try {
      res = await fetch(url);
    } catch {
      return null;
    }
    const { result: d = {} } = await res.json();
    if (!d.name) return null;

    const n = d.name.toLowerCase();
    let type = place._workshopType;
    if (normBrand && n.includes(normBrand.toLowerCase())) type = 'dealership';
    else if (/atu|pitstop|euromaster|vergölst/.test(n)) type = 'chain';
    else if (/getriebe/.test(n)) type = 'specialist_transmission';
    else if (/motor/.test(n)) type = 'specialist_engine';

    const out = {
      place_id: place.place_id,
      name: d.name,
      rating: Number(d.rating || 0),
      user_ratings_total: Number(d.user_ratings_total || 0),
      vicinity: d.vicinity || d.formatted_address || '',
      types: d.types || [],
      phone: d.formatted_phone_number || null,
      website: d.website || null,
      opening_hours: d.opening_hours || null,
      workshopType: type,
    };

    if (d.photos?.[0]?.photo_reference) {
      out.photoUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${d.photos[0].photo_reference}&key=${mapsKey}`;
    } else {
      out.photoUrl = 'https://placehold.co/400x400/94a3b8/ffffff?text=Carfify';
    }
    return out;
  });

  let workshops = (await Promise.all(detailsPromises)).filter(Boolean);

  /* ----- FINAL SORTING ----- */
  workshops.sort((a, b) =>
    VALID_TYPES[a.workshopType] - VALID_TYPES[b.workshopType] ||
    b.rating - a.rating
  );

  /* ----- RESPONSE ----- */
  const payload = {
    workshops,
    metadata: {
      vehicleBrand: normBrand || 'Nicht angegeben',
      hasVehicleBrand: normBrand.length > 0 && normBrand !== 'Nicht angegeben',
      requestedRadius: radNum,
      totalCount: workshops.length,
    },
  };

  CACHE.set(cacheKey, { ts: Date.now(), data: payload });
  setTimeout(() => CACHE.delete(cacheKey), TTL_MS).unref?.();
  res.status(200).json(payload);
}
```
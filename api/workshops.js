/* api/workshops.js */
import fetch from 'node-fetch';

const CACHE  = new Map();
const TTL_MS = 60_000;

const VALID_TYPES = Object.freeze({
  dealership:                0,
  chain:                     1,
  independent:               2,
  specialist_transmission:   3,
  specialist_engine:         3,
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

  if (!Number.isFinite(latNum) || !Number.isFinite(lonNum)) {
    return res.status(400).json({ message: 'Latitude und Longitude müssen gültige Zahlen sein.' });
  }
  if (!Number.isFinite(radNum) || radNum < 100 || radNum > 50_000) {
    return res.status(400).json({ message: 'Radius muss zwischen 100 und 50.000 Metern liegen.' });
  }

  const normProblem = problem.toLowerCase().replace(/\s+/g, ' ').trim();
  const normBrand   = vehicleBrand.trim();
  const cacheKey    = `${latNum}|${lonNum}|${normProblem}|${normBrand}|${radNum}`;

  const cached = CACHE.get(cacheKey);
  if (cached && Date.now() - cached.ts < TTL_MS) {
    return res.status(200).json(cached.data);
  }

  const queries = [];
  if (normBrand && normBrand !== 'Nicht angegeben') {
    queries.push({
      q: `${normBrand} Vertragswerkstatt`,
      type: 'dealership',
      radius: Math.min(Math.max(radNum, 10_000), 50_000),
    });
  }
  queries.push(
    { q: 'ATU Pitstop Euromaster Vergölst', type: 'chain', radius: Math.min(Math.max(radNum, 7_000), 50_000) },
    { q: 'freie Autowerkstatt KFZ-Werkstatt', type: 'independent', radius: radNum }
  );
  if (/getriebe|schaltung/.test(normProblem)) {
    queries.push({ q: 'Getriebespezialist Getriebewerkstatt', type: 'specialist_transmission', radius: 15_000 });
  }
  if (/motor/.test(normProblem)) {
    queries.push({ q: 'Motorspezialist Motorinstandsetzung', type: 'specialist_engine', radius: 15_000 });
  }

  const ps = new URLSearchParams({ language: 'de', type: 'car_repair', key: mapsKey });
  const searchUrls = queries.map(({ q, radius }) => {
    const u = new URL('https://maps.googleapis.com/maps/api/place/textsearch/json');
    ps.set('query', q);
    ps.set('location', `${latNum},${lonNum}`);
    ps.set('radius', radius);
    u.search = ps;
    return u.toString();
  });

  const searchSettle = await Promise.allSettled(
    searchUrls.map(u => fetch(u).then(r => r.json()))
  );

  const seen = new Set();
  const placeList = searchSettle
    .flatMap((result, idx) => {
      if (result.status !== 'fulfilled' || result.value.status !== 'OK') return [];
      return result.value.results
        .slice(0, 5)
        .map(p => ({ ...p, _t: queries[idx].type }));
    })
    .filter(p => seen.has(p.place_id) ? false : seen.add(p.place_id));

  if (!placeList.length) {
    CACHE.set(cacheKey, { ts: Date.now(), data: [] });
    return res.status(200).json({ workshops: [], metadata: { totalCount: 0 } });
  }

  const DETAIL_FIELDS =
    'name,rating,user_ratings_total,reviews,photos,vicinity,formatted_phone_number,opening_hours,website';
  const detailPs = new URLSearchParams({ fields: DETAIL_FIELDS, key: mapsKey, language: 'de' });

  const workshops = (
    await Promise.all(
      placeList.map(async (place) => {
        const u = new URL('https://maps.googleapis.com/maps/api/place/details/json');
        detailPs.set('place_id', place.place_id);
        u.search = detailPs;
        let res;
        try {
          res = await fetch(u);
        } catch {
          return null;
        }
        const { result: d = {} } = await res.json();
        if (!d.name) return null;

        const nameLC = d.name.toLowerCase();
        let type = place._t;
        if (normBrand && nameLC.includes(normBrand.toLowerCase())) type = 'dealership';
        else if (/atu|pitstop|euromaster|vergölst/.test(nameLC)) type = 'chain';
        else if (/getriebe/.test(nameLC)) type = 'specialist_transmission';
        else if (/motor/.test(nameLC)) type = 'specialist_engine';

        const out = {
          place_id:          d.place_id,
          name:              d.name,
          rating:            Number(d.rating || 0),
          user_ratings_total:Number(d.user_ratings_total || 0),
          vicinity:          d.vicinity || '',
          phone:             d.formatted_phone_number || null,
          website:           d.website || null,
          opening_hours:     d.opening_hours || null,
          workshopType:      type,
        };

        if (d.photos?.[0]?.photo_reference) {
          out.photoUrl = `https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=${d.photos[0].photo_reference}&key=${mapsKey}`;
        } else {
          out.photoUrl = `https://placehold.co/400x400/94a3b8/ffffff?text=${encodeURIComponent(d.name)}`;
        }
        return out;
      })
    )
  )
    .filter(Boolean)
    .sort((a, b) => VALID_TYPES[a.workshopType] - VALID_TYPES[b.workshopType] || b.rating - a.rating)
    .slice(0, 25);

  const payload = {
    workshops,
    metadata: {
      vehicleBrand:     normBrand || 'Nicht angegeben',
      hasVehicleBrand:  Boolean(normBrand) && normBrand !== 'Nicht angegeben',
      requestedRadius:  radNum,
      totalCount:       workshops.length,
    },
  };
  CACHE.set(cacheKey, { ts: Date.now(), data: payload });
  setTimeout(() => CACHE.delete(cacheKey), TTL_MS).unref?.();
  res.status(200).json(payload);
}
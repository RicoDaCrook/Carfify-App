// Carfify Service Worker v1.0
const CACHE_NAME = 'carfify-v1.0';
const urlsToCache = [
  '/',
  '/index.php',
  '/diagnosis.php',
  '/selling.php',
  '/assets/css/main.css',
  '/assets/js/app.js',
  '/pwa-manifest.json'
];

// Service Worker Installation
self.addEventListener('install', event => {
  console.log('Carfify SW: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Carfify SW: Caching app shell');
        return cache.addAll(urlsToCache.map(url => new Request(url, { cache: 'reload' })));
      })
      .then(() => {
        console.log('Carfify SW: Installation complete');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('Carfify SW: Installation failed', error);
      })
  );
});

// Service Worker Activation
self.addEventListener('activate', event => {
  console.log('Carfify SW: Activating...');
  event.waitUntil(
    Promise.all([
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('Carfify SW: Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      self.clients.claim()
    ])
  );
});

// Fetch Handler
self.addEventListener('fetch', event => {
  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        return response || fetch(event.request)
          .then(fetchResponse => {
            // Don't cache if not successful
            if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
              return fetchResponse;
            }

            // Clone the response
            const responseToCache = fetchResponse.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });

            return fetchResponse;
          });
      })
      .catch(() => {
        // Return offline fallback for navigation requests
        if (event.request.mode === 'navigate') {
          return caches.match('/index.php');
        }
      })
  );
});

// Background Sync (für Zukunft)
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    console.log('Carfify SW: Background sync triggered');
    // Hier können zukünftig Offline-Aktionen synchronisiert werden
  }
});
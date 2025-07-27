/**
 * Carfify Service Worker
 * PWA functionality with offline support
 */

const CACHE_NAME = 'carfify-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/assets/css/main.css',
  '/assets/js/app.js',
  '/assets/js/diagnosis.js',
  '/assets/js/selling.js',
  '/assets/js/animations.js',
  '/pwa-manifest.json',
  '/assets/images/icon-192.png',
  '/assets/images/icon-512.png',
  '/assets/images/favicon.png'
];

// Install event - cache resources
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Carfify cache opened');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }

  // API requests - network first, cache fallback
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Cache successful API responses
          if (response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Return cached version if offline
          return caches.match(event.request);
        })
    );
    return;
  }

  // Static assets - cache first, network fallback
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Return cached version if found
        if (response) {
          return response;
        }

        // Otherwise fetch from network
        return fetch(event.request).then((response) => {
          // Cache successful responses
          if (response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        });
      })
      .catch(() => {
        // Offline fallback
        if (event.request.destination === 'image') {
          return caches.match('/assets/images/offline.png');
        }
        return caches.match('/offline.html');
      })
  );
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    event.waitUntil(
      // Handle background sync tasks
      new Promise((resolve) => {
        console.log('Background sync triggered');
        resolve();
      })
    );
  }
});

// Push notifications
self.addEventListener('push', (event) => {
  const options = {
    body: event.data ? event.data.text() : 'Neue Benachrichtigung von Carfify',
    icon: '/assets/images/icon-192.png',
    badge: '/assets/images/icon-96.png',
    vibrate: [200, 100, 200],
    tag: 'carfify-notification',
    actions: [
      {
        action: 'open',
        title: 'Öffnen'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Carfify', options)
  );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow('/')
  );
});
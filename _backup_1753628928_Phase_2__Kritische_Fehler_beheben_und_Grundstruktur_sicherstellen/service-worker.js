/**
 * Carfify Service Worker
 * Stellt Offline-Fähigkeit für die PWA bereit
 */

const CACHE_NAME = 'carfify-v1';
const STATIC_CACHE_NAME = 'carfify-static-v1';
const DYNAMIC_CACHE_NAME = 'carfify-dynamic-v1';

// Zu cachnde statische Assets
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/css/styles.css',
  '/js/app.js',
  '/js/offline.js',
  '/assets/icons/icon-192x192.png',
  '/assets/icons/icon-512x512.png',
  '/offline.html'
];

// Zu cachnde Anleitungen und Hilfsmittel
const CONTENT_ASSETS = [
  '/guides/engine-troubleshooting.html',
  '/guides/brake-maintenance.html',
  '/guides/oil-change-guide.html',
  '/guides/battery-check.html',
  '/guides/tire-maintenance.html'
];

// Installations-Event
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        return caches.open(CACHE_NAME);
      })
      .then((cache) => {
        console.log('[Service Worker] Caching content assets');
        return cache.addAll(CONTENT_ASSETS);
      })
      .then(() => {
        console.log('[Service Worker] Skip waiting');
        return self.skipWaiting();
      })
  );
});

// Aktivierungs-Event
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== STATIC_CACHE_NAME && 
                cacheName !== DYNAMIC_CACHE_NAME && 
                cacheName !== CACHE_NAME) {
              console.log('[Service Worker] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('[Service Worker] Claiming clients');
        return self.clients.claim();
      })
  );
});

// Fetch-Event
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Nicht für Chrome-Erweiterungen oder andere Browser-Internes cachen
  if (url.protocol === 'chrome-extension:' || 
      url.protocol === 'chrome:' ||
      url.protocol === 'moz-extension:' ||
      url.protocol === 'safari-extension:') {
    return;
  }
  
  // API-Aufrufe - Netzwerk first, dann Cache
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirstStrategy(request));
    return;
  }
  
  // Statische Assets - Cache first
  if (STATIC_ASSETS.includes(url.pathname) || 
      url.pathname.startsWith('/css/') || 
      url.pathname.startsWith('/js/') ||
      url.pathname.startsWith('/assets/')) {
    event.respondWith(cacheFirstStrategy(request));
    return;
  }
  
  // Anleitungen und Inhalte - Cache first
  if (CONTENT_ASSETS.includes(url.pathname) || 
      url.pathname.startsWith('/guides/')) {
    event.respondWith(cacheFirstStrategy(request));
    return;
  }
  
  // Standard-Strategie: Netzwerk first mit Offline-Seite als Fallback
  event.respondWith(networkFirstWithOfflineFallback(request));
});

// Cache-First Strategie
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[Service Worker] Cache first failed:', error);
    return new Response('Offline', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Network-First Strategie
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[Service Worker] Network first failed, trying cache:', error);
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    return new Response(JSON.stringify({
      error: 'Offline',
      message: 'Keine Internetverbindung verfügbar'
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Network-First mit Offline-Fallback
async function networkFirstWithOfflineFallback(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[Service Worker] Network failed, checking cache:', error);
    
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Offline-Seite als letztes Fallback
    const offlineResponse = await caches.match('/offline.html');
    if (offlineResponse) {
      return offlineResponse;
    }
    
    return new Response('Offline', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Background Sync für Offline-Aktionen
self.addEventListener('sync', (event) => {
  console.log('[Service Worker] Sync event triggered:', event.tag);
  
  if (event.tag === 'background-sync') {
    event.waitUntil(handleBackgroundSync());
  }
});

async function handleBackgroundSync() {
  // Hier könnte man gespeicherte Offline-Aktionen verarbeiten
  console.log('[Service Worker] Processing background sync...');
  
  // Beispiel: Gespeicherte Diagnose-Ergebnisse senden
  const cache = await caches.open(DYNAMIC_CACHE_NAME);
  const requests = await cache.keys();
  
  return Promise.all(
    requests.map(async (request) => {
      if (request.url.includes('/api/diagnosis/')) {
        const response = await cache.match(request);
        if (response) {
          try {
            await fetch(request);
            await cache.delete(request);
            console.log('[Service Worker] Synced diagnosis data');
          } catch (error) {
            console.log('[Service Worker] Sync failed, will retry:', error);
          }
        }
      }
    })
  );
}

// Push-Notifications (für zukünftige Erweiterungen)
self.addEventListener('push', (event) => {
  console.log('[Service Worker] Push received:', event);
  
  const options = {
    body: 'Neue Werkstatt in deiner Nähe gefunden!',
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/badge-72x72.png',
    vibrate: [200, 100, 200],
    tag: 'workshop-notification',
    actions: [
      {
        action: 'view',
        title: 'Ansehen',
        icon: '/assets/icons/view.png'
      },
      {
        action: 'dismiss',
        title: 'Später',
        icon: '/assets/icons/dismiss.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('Carfify', options)
  );
});

// Notification-Click-Handler
self.addEventListener('notificationclick', (event) => {
  console.log('[Service Worker] Notification click:', event);
  
  event.notification.close();
  
  if (event.action === 'view') {
    event.waitUntil(
      clients.openWindow('/workshops')
    );
  }
});
const CACHE_NAME = 'merchantrack-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/offline.html',
  '/favicon.ico',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://code.jquery.com/jquery-3.6.0.min.js'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_ASSETS.map(url => {
          try {
            return new Request(url, { mode: 'no-cors' });
          } catch (e) {
            return url;
          }
        })).catch(err => {
          console.log('[Service Worker] Cache addAll error:', err);
          // Continue even if some assets fail to cache
        });
      })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip chrome-extension and other non-http(s) requests
  if (!event.request.url.startsWith('http')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then((cachedResponse) => {
        // Return cached version if available
        if (cachedResponse) {
          return cachedResponse;
        }

        // Otherwise fetch from network
        return fetch(event.request)
          .then((response) => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone the response
            const responseToCache = response.clone();

            // Cache successful responses
            caches.open(CACHE_NAME)
              .then((cache) => {
                // Only cache GET requests
                if (event.request.method === 'GET') {
                  cache.put(event.request, responseToCache);
                }
              });

            return response;
          })
          .catch(() => {
            // If fetch fails and it's a navigation request, show offline page
            if (event.request.mode === 'navigate') {
              return caches.match(OFFLINE_URL);
            }
            
            // For other requests, return a basic offline response
            return new Response('Offline - No internet connection', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({
                'Content-Type': 'text/plain'
              })
            });
          });
      })
  );
});

// Handle background sync for offline sales
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-sales') {
    console.log('[Service Worker] Background sync: sync-sales');
    event.waitUntil(syncPendingSales());
  }
});

// Function to sync pending sales when online
async function syncPendingSales() {
  try {
    // Get pending sales from IndexedDB
    const pendingSales = await getPendingSales();
    
    for (const sale of pendingSales) {
      try {
        const response = await fetch('/pos/process-sale', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken()
          },
          body: JSON.stringify(sale)
        });

        if (response.ok) {
          // Remove from pending sales
          await removePendingSale(sale.id);
          console.log('[Service Worker] Synced sale:', sale.id);
        }
      } catch (error) {
        console.error('[Service Worker] Error syncing sale:', error);
      }
    }
  } catch (error) {
    console.error('[Service Worker] Error in syncPendingSales:', error);
  }
}

// Helper functions (will be used with IndexedDB)
function getCSRFToken() {
  // CSRF token is stored in meta tag, we'll handle this in the main app
  return '';
}

async function getPendingSales() {
  // This will be implemented with IndexedDB
  return [];
}

async function removePendingSale(id) {
  // This will be implemented with IndexedDB
}


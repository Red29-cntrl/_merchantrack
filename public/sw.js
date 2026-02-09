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

// IndexedDB configuration (must match main page)
const DB_NAME = 'merchantrack_offline';
const DB_VERSION = 1;
const STORE_NAME = 'pending_sales';
const CSRF_STORE_NAME = 'csrf_token';

// Initialize IndexedDB
async function initDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      
      // Create pending_sales store if it doesn't exist
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        store.createIndex('timestamp', 'timestamp', { unique: false });
        store.createIndex('synced', 'synced', { unique: false });
      }
      
      // Create csrf_token store if it doesn't exist
      if (!db.objectStoreNames.contains(CSRF_STORE_NAME)) {
        db.createObjectStore(CSRF_STORE_NAME, { keyPath: 'id' });
      }
    };
  });
}

// Get pending sales from IndexedDB (only unsynced ones)
async function getPendingSales() {
  const db = await initDB();
  
  return new Promise((resolve, reject) => {
    const transaction = db.transaction([STORE_NAME], 'readonly');
    const store = transaction.objectStore(STORE_NAME);
    const index = store.index('synced');
    const request = index.getAll(false); // Get only unsynced sales (synced === false)
    
    request.onsuccess = () => resolve(request.result || []);
    request.onerror = () => reject(request.error);
  });
}

// Remove synced sale from IndexedDB
async function removePendingSale(id) {
  const db = await initDB();
  
  return new Promise((resolve, reject) => {
    const transaction = db.transaction([STORE_NAME], 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    const request = store.delete(id);
    
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

// Get CSRF token from IndexedDB
async function getCSRFToken() {
  try {
    const db = await initDB();
    
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([CSRF_STORE_NAME], 'readonly');
      const store = transaction.objectStore(CSRF_STORE_NAME);
      const request = store.get('token');
      
      request.onsuccess = () => {
        const result = request.result;
        resolve(result ? result.value : '');
      };
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.error('[Service Worker] Error getting CSRF token:', error);
    return '';
  }
}

// Store CSRF token in IndexedDB (called from main page via postMessage)
async function storeCSRFToken(token) {
  try {
    const db = await initDB();
    
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([CSRF_STORE_NAME], 'readwrite');
      const store = transaction.objectStore(CSRF_STORE_NAME);
      const request = store.put({ id: 'token', value: token });
      
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.error('[Service Worker] Error storing CSRF token:', error);
  }
}

// Handle messages from the main page (for CSRF token)
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'CSRF_TOKEN') {
    storeCSRFToken(event.data.token);
  }
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
    // Get pending sales from IndexedDB (only unsynced ones)
    const pendingSales = await getPendingSales();
    
    if (pendingSales.length === 0) {
      console.log('[Service Worker] No pending sales to sync');
      return;
    }
    
    console.log(`[Service Worker] Syncing ${pendingSales.length} pending sales...`);
    
    // Get CSRF token
    const csrfToken = await getCSRFToken();
    
    if (!csrfToken) {
      console.error('[Service Worker] No CSRF token available. Requesting from client...');
      // Request CSRF token from client
      const clients = await self.clients.matchAll();
      clients.forEach(client => {
        client.postMessage({ type: 'REQUEST_CSRF_TOKEN' });
      });
      return;
    }
    
    for (const sale of pendingSales) {
      try {
        // Format data like the main page does (remove product_name, keep only product_id)
        const syncData = {
          items: (sale.items || []).map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            unit: item.unit,
            unit_price: item.unit_price,
            subtotal: item.subtotal
          })),
          subtotal: sale.subtotal,
          tax: sale.tax || 0,
          discount: sale.discount || 0,
          total: sale.total,
          payment_method: sale.payment_method
        };
        
        const response = await fetch('/pos/process-sale', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify(syncData)
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            // Remove from pending sales
            await removePendingSale(sale.id);
            console.log('[Service Worker] Synced sale:', sale.id);
          } else {
            console.error('[Service Worker] Sync failed for sale:', sale.id, result);
          }
        } else {
          const errorText = await response.text();
          console.error('[Service Worker] Sync failed for sale:', sale.id, response.status, errorText);
          
          // If CSRF token is invalid, request a new one
          if (response.status === 419 || response.status === 403) {
            const clients = await self.clients.matchAll();
            clients.forEach(client => {
              client.postMessage({ type: 'REQUEST_CSRF_TOKEN' });
            });
          }
        }
      } catch (error) {
        console.error('[Service Worker] Error syncing sale:', sale.id, error);
      }
    }
  } catch (error) {
    console.error('[Service Worker] Error in syncPendingSales:', error);
  }
}


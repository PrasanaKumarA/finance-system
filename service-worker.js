const CACHE_NAME = 'financehub-cache-v8';
const urlsToCache = [
    './',
    './index.php',
    './login.php',
    './assets/css/style.css',
    './assets/images/favi.JPG',
    './manifest.json'
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return Promise.allSettled(
                    urlsToCache.map(url => {
                        return fetch(new Request(url, { cache: 'reload' }))
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`Request failed for ${url}`);
                                }
                                return cache.put(url, response);
                            })
                            .catch(error => console.warn('SW: Failed to cache:', url, error.message));
                    })
                );
            })
    );
});

self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then(networkResponse => {
                // Only cache same-origin requests
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                return caches.match(event.request);
            })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

const CACHE_NAME = 'financehub-cache-v5';
const urlsToCache = [
    './',
    './index.php',
    './login.php',
    './assets/css/style.css',
    './assets/images/favi.JPG'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                // Use Promise.allSettled or catch individual so one failure doesn't block the rest
                return Promise.allSettled(
                    urlsToCache.map(url => {
                        return fetch(new Request(url, { cache: 'reload' }))
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`Request failed for ${url}`);
                                }
                                return cache.put(url, response);
                            })
                            .catch(error => console.error('Failed to cache:', url, error));
                    })
                );
            })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        fetch(event.request)
            .then(networkResponse => {
                return caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, networkResponse.clone());
                    return networkResponse;
                });
            })
            .catch(() => {
                return caches.match(event.request);
            })
    );
});

self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

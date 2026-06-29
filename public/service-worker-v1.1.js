self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((cached) => {
            const fetchPromise = fetch(event.request)
                .then((networkResponse) => {
                    const copy = networkResponse.clone();
                    caches.open('easyamap-sw-1.1').then((cache) => {
                        cache.put(event.request, copy);
                    });
                    return networkResponse;
                })
                .catch(() => cached);

            return cached || fetchPromise;
        })
    );
});
const urlsToCache = [
    '/images/logo-easy-amap-icon.png',
    '/bootstrap/css/bootstrap-yeti.min.css',
    '/css/amap.css',
    '/css/portal.css',
    '/css/print.css',
    '/css/select2.css',
    '/js/jquery-1.11.2.min.js',
    '/js/select2.js',
    '/bootstrap/js/bootstrap.min.js',
    '/js/amap.js'
];
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open('easyamap-sw-1.1').then(function(cache) {
            return cache.addAll(urlsToCache);
        })
    );
});

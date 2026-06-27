self.addEventListener('fetch', event => {
    event.respondWith(
        caches.open('easyamap-sw-1.0').then(cache => {
            return cache.match(event.request).then(response => {
                if (navigator.onLine === false) {
                    return response;
                }
                return  fetch(event.request)
                    .then(response => {
                        cache.put(event.request, response.clone());
                        return response;
                    });
            });
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
        caches.open('easyamap-sw-1.0').then(function(cache) {
            return cache.addAll(urlsToCache);
        })
    );
});

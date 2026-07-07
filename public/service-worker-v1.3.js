const CACHE_NAME = 'easyamap-sw-1.3';

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        event.respondWith(fetch(event.request));
        return;
    }
    const cacheable = ['script', 'style', 'image', 'font'];
    if (!cacheable.includes(event.request.destination)) {
        event.respondWith(fetch(event.request));
        return;
    }
    event.respondWith(
        caches.match(event.request).then((cached) => {
            const fetchPromise = fetch(event.request)
                .then((networkResponse) => {
                    const copy = networkResponse.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => cache.put(event.request, copy))
                        .catch(console.error);
                    return networkResponse;
                })
                .catch(() => cached);
            return cached || fetchPromise;
        })
    );
});
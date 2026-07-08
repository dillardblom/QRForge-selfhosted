// Minimal service worker: only makes the app installable and caches truly static
// assets. Deliberately never caches PHP pages or the presets/bulk_action/qrcode_image
// endpoints - those carry session-specific and CSRF-sensitive content.
const CACHE_NAME = 'qrcode-static-v1';
const STATIC_ASSET_PATTERN = /\.(css|js|png|jpg|jpeg|svg|gif|woff2?|ttf)$/;

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || url.origin !== self.location.origin || !STATIC_ASSET_PATTERN.test(url.pathname)) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.match(event.request).then(function (cached) {
                const fetchPromise = fetch(event.request).then(function (response) {
                    cache.put(event.request, response.clone());
                    return response;
                });
                return cached || fetchPromise;
            });
        })
    );
});

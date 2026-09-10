const CACHE_VERSION = 'v15';
const CACHE_NAME = `online-exam-pwa-${CACHE_VERSION}`;

const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/manifest.webmanifest',
    '/favicon.png',
    '/pwa-192.png',
    '/pwa-512.png',
    '/pwa-maskable-192.png',
    '/pwa-maskable-512.png',
    '/apple-touch-icon.png',
    '/favicon.ico',
    '/ico.svg',
    '/fonts/material-symbols-outlined.woff2'
];

// Install Event: Precaching and instant activation
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return Promise.all(
                    PRECACHE_ASSETS.map((url) =>
                        cache.add(url).catch((err) => {
                            console.warn('[SW] Precache skipped for:', url, err);
                        })
                    )
                );
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Event: Clean up stale caches & claim clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => {
                return Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => {
                            console.log('[SW] Removing old cache:', key);
                            return caches.delete(key);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch Event: Smart routing & caching strategies
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only process GET requests
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Bypass API calls, auth checks, and dev server hot module reloads
    if (
        url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/@vite') ||
        url.pathname.startsWith('/@fs') ||
        url.port === '5173' ||
        url.searchParams.has('hot')
    ) {
        return;
    }

    // 1. Navigation requests (HTML SPA Shell) -> Network First with Cache Fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return caches.match('/');
                })
        );
        return;
    }

    // 2. Static Assets (Images, Fonts, Scripts, Styles) -> Cache First with Network Fallback
    const isStaticAsset =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.json') ||
        url.pathname.endsWith('.webmanifest');

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Update cache in background if stale
                    fetch(request)
                        .then((networkResponse) => {
                            if (networkResponse && networkResponse.status === 200) {
                                caches.open(CACHE_NAME).then((cache) => {
                                    cache.put(request, networkResponse);
                                });
                            }
                        })
                        .catch(() => {});
                    return cachedResponse;
                }

                return fetch(request)
                    .then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => {
                        // Return empty or cached fallback if offline
                        return caches.match(request);
                    });
            })
        );
        return;
    }

    // Default strategy: Network First
    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response && response.status === 200) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});

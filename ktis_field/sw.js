/**
 * sw.js — Service Worker for KTIS SMART FIELD
 * PWA Offline Caching & Background Network Fallback
 */

const CACHE_NAME = 'ktis-field-v2';
const STATIC_ASSETS = [
    './',
    'index.php',
    'harvester.php',
    'login.php',
    'global_smoothness.css',
    'icon/iconweb.png',
    'manifest.json',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700;800&display=swap',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// 1. Install Event: Pre-cache core shell assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(err => {
                console.warn('SW pre-cache item error:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// 2. Activate Event: Clean up outdated caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch Event: Network-first for dynamic pages, Cache-first for static assets
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET and API/POST requests from cache
    if (request.method !== 'GET' || url.pathname.endsWith('post_create.php') || url.pathname.endsWith('api_offline_sync.php')) {
        return;
    }

    // Static Assets: Cache-first with background network update
    if (request.destination === 'style' || request.destination === 'script' || request.destination === 'image' || request.destination === 'font') {
        event.respondWith(
            caches.match(request).then(cached => {
                if (cached) return cached;
                return fetch(request).then(response => {
                    if (response && response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
                    }
                    return response;
                }).catch(() => cached);
            })
        );
        return;
    }

    // HTML / PHP Pages: Network-first with Cache fallback
    event.respondWith(
        fetch(request).then(response => {
            if (response && response.status === 200) {
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
            }
            return response;
        }).catch(() => {
            return caches.match(request).then(cached => {
                if (cached) return cached;
                // Fallback to offline page or cached harvester.php
                return caches.match('harvester.php');
            });
        })
    );
});

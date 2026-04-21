// MMEX Web — service worker (PWA installability + offline léger)

const VERSION = 'mmex-v2.0.8';
const CACHE   = 'mmex-shell-' + VERSION;

// Shell minimal — les URLs sont relatives au scope du SW (dossier où sw.js est servi)
const SHELL = [
  './',
  './assets/style.css',
  './assets/app.js',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png',
  './assets/icons/icon.svg',
  './assets/icons/apple-touch-icon.png',
  './manifest.webmanifest',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE)
      .then((c) => c.addAll(SHELL).catch(() => { /* ignore erreurs individuelles */ }))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys
          .filter((k) => k.startsWith('mmex-shell-') && k !== CACHE)
          .map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // On ne cache jamais les endpoints API, l'URL desktop, ni la file d'attente
  // (qui dépend de l'état serveur en temps réel).
  const path = url.pathname;
  const NEVER_CACHE = [
    '/api/', '/services.php', '/transaction', '/logout',
    '/login', '/setup', '/invite/', '/settings',
  ];
  if (NEVER_CACHE.some((p) => path.includes(p))) {
    return; // laisser le réseau gérer (et échouer si offline)
  }

  // Pages HTML : network-first, fallback cache si offline
  if (req.headers.get('accept') && req.headers.get('accept').includes('text/html')) {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
          return res;
        })
        .catch(() => caches.match(req).then((r) => r || caches.match('./')))
    );
    return;
  }

  // Assets statiques : cache-first
  event.respondWith(
    caches.match(req).then((cached) => cached || fetch(req).then((r) => {
      if (r.ok && (r.type === 'basic' || r.type === 'default')) {
        const copy = r.clone();
        caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
      }
      return r;
    }))
  );
});

// Permet à la page de demander un skipWaiting pour activer une nouvelle version
self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') self.skipWaiting();
});

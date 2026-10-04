// v2: solo se cachean recursos estáticos. Las páginas PHP (con datos de sesión)
// nunca se guardan en caché, para que no queden visibles tras cerrar sesión.
const CACHE_NAME = 'soporte-otic-v2';
const APP_SHELL = [
  './manifest.json',
  './assets/css/style.css'
];
const ESTATICO = /\.(?:css|png|jpg|jpeg|svg|ico|webp|woff2?)$/i;

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(APP_SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== self.location.origin) return;
  if (url.pathname.includes('/uploads/') || !ESTATICO.test(url.pathname)) return; // red directa
  event.respondWith(
    fetch(req).then(response => {
      if (response.ok) {
        const copy = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(req, copy));
      }
      return response;
    }).catch(() => caches.match(req))
  );
});

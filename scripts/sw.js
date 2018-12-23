var cacheName = 'v1:static';

self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(cacheName).then(function(cache) {
            return cache.addAll([
				'../bookmarks.css',
                '../images/1-9-2.jpg',
				'../images/account.jpg',
				'../images/bookmarks.png',
				'../images/fflgo.png',
				'../images/pclgo.png',
                '../scripts/bookmarks.js',
				'../scripts/sw.js',
                '../scripts/jquery-3.3.1.min.js'
            ].map(url => new Request(url, {credentials: 'same-origin'}))).then(function() {
                self.skipWaiting();
            });
        })
    );
});

self.addEventListener('activate', function(event) {
  console.log('');
});

self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request).then(function(response) {
            if (response) {
                return response;
            }
            return fetch(event.request);
        })
    );
});
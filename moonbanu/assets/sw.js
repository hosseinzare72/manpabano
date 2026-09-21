/*!
 * ماه‌بانو | service worker 3.0.0
 *
 * کاملاً مستقل از service worker سایت:
 *  - نام کش با پیشوند و نسخهٔ اختصاصی (هیچ کش دیگری پاک نمی‌شود).
 *  - فقط درخواست‌های داخل scope اپ را پاسخ می‌دهد؛ بقیهٔ سایت دست‌نخورده
 *    به SW خودش یا شبکه می‌رسد.
 *  - REST، ادمین، ورود و کالبک پرداخت هرگز کش نمی‌شوند.
 */
var MB_VERSION = '{{MB_VERSION}}';
if (MB_VERSION.indexOf('{{') === 0) { MB_VERSION = '3.0.0'; }

var MB_CACHE = 'moonbanu-app-v' + MB_VERSION;
var MB_PREFIX = 'moonbanu-app-';

/* پایهٔ دارایی‌ها؛ PHP جای نگه‌دارنده نشانی کامل پوشه assets را می‌گذارد. */
var MB_BASE = '{{MB_ASSETS}}';
if (MB_BASE.indexOf('{{') === 0) {
	MB_BASE = new URL('./', self.location.href).href;
}

/* scope اپ: SW فقط همین مسیر را کنترل می‌کند. */
var MB_SCOPE = new URL(self.registration ? self.registration.scope : './', self.location.href).pathname;

/* M12 — کش مقالات ذخیره‌شده. */
var MB_ARTICLES = MB_CACHE + '-articles';

var MB_SHELL = [
	MB_BASE + 'css/style.css',
	MB_BASE + 'css/print.css',
	MB_BASE + 'js/app.js',
	MB_BASE + 'js/qr.js',
	MB_BASE + 'icon-192.png',
	MB_BASE + 'icon-512.png',
	MB_BASE + 'icon-maskable-512.png',
	MB_BASE + 'apple-touch-icon.png',
	MB_BASE + 'fonts/Vazirmatn-400.woff2',
	MB_BASE + 'fonts/Vazirmatn-500.woff2',
	MB_BASE + 'fonts/Vazirmatn-600.woff2',
	MB_BASE + 'fonts/Vazirmatn-700.woff2',
	MB_BASE + 'fonts/Vazirmatn-800.woff2'
];

self.addEventListener('install', function (event) {
	event.waitUntil(
		caches.open(MB_CACHE).then(function (cache) {
			return Promise.all(MB_SHELL.map(function (url) {
				return cache.add(new Request(url, { cache: 'reload' })).catch(function () { return null; });
			}));
		}).then(function () { return self.skipWaiting(); })
	);
});

self.addEventListener('activate', function (event) {
	event.waitUntil(
		caches.keys().then(function (keys) {
			return Promise.all(keys.map(function (key) {
				/* فقط کش‌های نسخه‌های قدیمی خودِ ماه‌بانو پاک می‌شود. */
				if (key.indexOf(MB_PREFIX) !== 0 && key.indexOf('moonbanu-shell-') !== 0) { return null; }
				if (key === MB_CACHE || key === MB_ARTICLES) { return null; }
				return caches.delete(key);
			}));
		}).then(function () { return self.clients.claim(); })
	);
});

self.addEventListener('message', function (event) {
	var data = event.data;
	if (data === 'mb-skip-waiting') { self.skipWaiting(); return; }

	/* M12 — اپ فهرست مقاله‌های ذخیره‌شده را می‌فرستد تا آفلاین در دسترس باشند. */
	if (data && data.type === 'mb-cache-articles' && Array.isArray(data.urls)) {
		event.waitUntil(
			caches.open(MB_ARTICLES).then(function (cache) {
				return Promise.all(data.urls.map(function (url) {
					return cache.add(new Request(url, { cache: 'reload' })).catch(function () { return null; });
				}));
			})
		);
	}
});

/* M6 — اعلان محلی: بدون هیچ سرویس push بیرونی. */
self.addEventListener('notificationclick', function (event) {
	event.notification.close();
	var target = (event.notification.data && event.notification.data.url) || MB_SCOPE;
	event.waitUntil(
		self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
			for (var i = 0; i < list.length; i++) {
				if (list[i].url.indexOf(MB_SCOPE) !== -1 && 'focus' in list[i]) { return list[i].focus(); }
			}
			return self.clients.openWindow ? self.clients.openWindow(target) : null;
		})
	);
});

/* M6 — periodic background sync در صورت پشتیبانی مرورگر. */
self.addEventListener('periodicsync', function (event) {
	if (event.tag !== 'mb-reminders') { return; }
	event.waitUntil(
		fetch(MB_SCOPE + '?mb_ping=1', { credentials: 'same-origin' }).catch(function () { return null; })
	);
});

function isBypassed(url) {
	return url.pathname.indexOf('/wp-json/') !== -1 ||
		url.pathname.indexOf('/wp-admin/') !== -1 ||
		url.pathname.indexOf('/wp-login.php') !== -1 ||
		url.pathname.indexOf('/wp-cron.php') !== -1 ||
		url.search.indexOf('mb_verify') !== -1 ||
		url.search.indexOf('mb_invite') !== -1 ||
		url.search.indexOf('mb_sw') !== -1 ||
		url.search.indexOf('mb_manifest') !== -1 ||
		url.search.indexOf('preview=') !== -1;
}

/* دارایی پلاگین است؟ (این‌ها بیرون از scope صفحه هم هستند و باید کش شوند.) */
function isOwnAsset(url) {
	return url.href.indexOf(MB_BASE) === 0;
}

/* درون scope اپ است؟ */
function inScope(url) {
	return url.pathname.indexOf(MB_SCOPE) === 0;
}

self.addEventListener('fetch', function (event) {
	var request = event.request;
	if (request.method !== 'GET') { return; }

	var url;
	try { url = new URL(request.url); } catch (e) { return; }
	if (url.origin !== self.location.origin || isBypassed(url)) { return; }

	var own = isOwnAsset(url);
	if (!own && !inScope(url)) { return; } // بقیهٔ سایت را دست نمی‌زنیم.

	/* دارایی‌های ثابت: ابتدا کش، سپس شبکه در پس‌زمینه. */
	if (own || /\.(?:css|js|woff2|png|jpg|jpeg|svg|webp|webmanifest)$/.test(url.pathname)) {
		event.respondWith(
			caches.match(request).then(function (cached) {
				var fresh = fetch(request).then(function (response) {
					if (response && response.ok) {
						var copy = response.clone();
						caches.open(MB_CACHE).then(function (cache) { cache.put(request, copy); });
					}
					return response;
				}).catch(function () { return cached || Response.error(); });
				return cached || fresh;
			})
		);
		return;
	}

	/* صفحه‌ها: ابتدا شبکه، در نبود شبکه صفحهٔ آفلاین اپ. */
	if (request.mode === 'navigate') {
		event.respondWith(
			fetch(request).catch(function () {
				return caches.match(request).then(function (cached) {
					return cached || offlinePage();
				});
			})
		);
	}
});

function offlinePage() {
	var html = '<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="utf-8">' +
		'<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">' +
		'<title>ماه‌بانو | آفلاین</title>' +
		'<link rel="stylesheet" href="' + MB_BASE + 'css/style.css"></head>' +
		'<body class="mb-standalone"><div class="mb-app" dir="rtl"><div class="mb-bg"></div><div class="mb-view">' +
		'<div class="scr"><div class="pad"><div class="glass card center">' +
		'<div class="brand-sm gold-text">ماه‌بانو</div>' +
		'<h1 class="h1">اتصال اینترنت قطع است</h1>' +
		'<p class="body">داده‌های ثبت‌شده‌ات روی سرور محفوظ است. با برگشت اینترنت دوباره امتحان کن.</p>' +
		'<button type="button" class="btn gold wide" onclick="location.reload()">تلاش دوباره</button>' +
		'<p class="small disclaimer">این اپ جایگزین تشخیص یا درمان پزشکی نیست.</p>' +
		'</div></div></div></div></div></body></html>';
	return new Response(html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

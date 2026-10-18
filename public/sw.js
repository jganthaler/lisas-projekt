/*
 | The service worker of the app.
 |
 | Coolventory lives on the server: the drawers, the items and their best before
 | dates are loaded for every page, so this worker never answers a page from its
 | own storage - a stale freezer would be worse than none. What it does:
 |
 | - It makes the app installable. Chrome, Edge and Android only offer "Install"
 |   (and only fire the beforeinstallprompt event the profile waits for) when a
 |   service worker with a fetch handler is registered.
 | - It answers a navigation without network with the offline page
 |   (public/offline.html). The page is part of the precache below, so it is on
 |   the phone from the very first visit.
 | - It keeps the built files of the app (public/build/*) in a cache: their names
 |   carry a hash, their content can never change, so a second visit paints
 |   without asking the server for them again.
 |
 | Version: the name of the cache carries VERSION. The browser only fetches this
 | script again when its content changed, and only that install refills the
 | cache - so whoever edits the offline page (or the manifest, or the icon) has
 | to raise VERSION here, otherwise the phone keeps the old copy. Bumping it
 | also drops the built files of the deployments before it.
 |
 | Registered by resources/js/app.js with updateViaCache: "none", so a
 | deployment never has to wait for the HTTP cache of the browser.
 */

const VERSION = "v1";

const CACHE_PREFIX = "coolventory-";
const SHELL_CACHE = CACHE_PREFIX + "shell-" + VERSION;

/* Everything a page of the app needs while the network is gone. The offline
   page carries its own styles and script, so the icon is the only file it
   asks for. */
const PRECACHE_URLS = ["/offline.html", "/favicon.png", "/manifest.json"];

/* The answer for a navigation that cannot reach the server. */
const OFFLINE_URL = "/offline.html";

/* Files of the app that may change under a name that stays the same (the icon,
   the word mark, the manifest) - they are asked for again whenever the server
   is reachable. */
const ROOT_FILES = ["/favicon.png", "/favicon.ico", "/logo.svg", "/manifest.json"];

self.addEventListener("install", (event) => {
    event.waitUntil(precache());
});

async function precache() {
    const cache = await caches.open(SHELL_CACHE);

    // "reload" steps past the HTTP cache of the browser: the precache must never
    // fill itself with a stale copy of a file it is meant to keep.
    await cache.addAll(PRECACHE_URLS.map((url) => new Request(url, { cache: "reload" })));

    // The new worker takes over right away. Nothing can go stale by that: no
    // page of the app is answered from the cache.
    await self.skipWaiting();
}

self.addEventListener("activate", (event) => {
    event.waitUntil(forgetOldCaches());
});

async function forgetOldCaches() {
    const names = await caches.keys();

    await Promise.all(
        names
            .filter((name) => name.startsWith(CACHE_PREFIX) && name !== SHELL_CACHE)
            .map((name) => caches.delete(name))
    );

    await self.clients.claim();
}

self.addEventListener("fetch", (event) => {
    const request = event.request;
    const url = new URL(request.url);

    /*
     | Livewire posts, the logout, an uploaded picture - everything that changes
     | data goes straight to the server. Requests to another origin (the fonts,
     | if a deployment serves them elsewhere) are none of our business either.
     */
    if (request.method !== "GET" || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === "navigate") {
        event.respondWith(handleNavigation(request));

        return;
    }

    if (isStaticFile(url)) {
        event.respondWith(handleStaticFile(request, url));
    }
});

/**
 * Whether the app ships this file itself and it is not personal: the built
 * files, the icon, the word mark and the manifest. Pictures of people
 * (/avatars/…) stay out of the cache - they belong to the browser, which
 * forgets them on its own.
 */
function isStaticFile(url) {
    return url.pathname.startsWith("/build/") || ROOT_FILES.includes(url.pathname);
}

/**
 * A page: always from the server, because the items behind it live there. The
 * offline page takes its place when there is no network.
 */
async function handleNavigation(request) {
    try {
        return await fetch(request);
    } catch (error) {
        return (await caches.match(OFFLINE_URL)) ?? Response.error();
    }
}

async function handleStaticFile(request, url) {
    const cache = await caches.open(SHELL_CACHE);

    // A built file is named after its content: what the cache holds is exactly
    // what the page asked for.
    if (url.pathname.startsWith("/build/")) {
        const cached = await cache.match(request);

        if (cached !== undefined) {
            return cached;
        }
    }

    try {
        const response = await fetch(request);

        if (response.ok) {
            await cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        // No network: whatever the cache still holds has to do.
        return (await cache.match(request)) ?? Response.error();
    }
}

/*
 | Loading bar
 |
 | Every Livewire request (wire:click, wire:model, form submits) makes the thin
 | bar at the very top of the page fill up, so a slow phone never leaves the
 | user guessing. Requests that finish quickly never show the bar because it
 | only appears after a short delay. Link navigation (wire:navigate) is covered
 | by Livewire's own progress bar, which gets the brand colour from the CSS
 | variable --livewire-progress-bar-color.
 */

const SHOW_DELAY = 150;

let showTimer = null;
let hideTimer = null;

function loadingBar() {
    return document.getElementById('loading-bar');
}

function startLoading() {
    const element = loadingBar();

    if (element === null || element.dataset.active === 'true') {
        return;
    }

    clearTimeout(hideTimer);

    showTimer = setTimeout(() => {
        const fill = element.firstElementChild;

        if (fill === null) {
            return;
        }

        element.dataset.active = 'true';

        // Jump back to zero, then crawl towards 90 % until the answer arrives.
        fill.style.transition = 'none';
        fill.style.width = '0%';

        requestAnimationFrame(() => {
            fill.style.transition = 'width 8s cubic-bezier(0.1, 0.6, 0.2, 1)';
            fill.style.width = '90%';
        });
    }, SHOW_DELAY);
}

function stopLoading() {
    const element = loadingBar();

    if (element === null) {
        return;
    }

    clearTimeout(showTimer);

    if (element.dataset.active !== 'true') {
        return;
    }

    const fill = element.firstElementChild;

    if (fill === null) {
        return;
    }

    fill.style.transition = 'width 180ms ease';
    fill.style.width = '100%';

    hideTimer = setTimeout(() => {
        element.dataset.active = 'false';
        fill.style.transition = 'none';
        fill.style.width = '0%';
    }, 220);
}

document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ succeed, fail }) => {
        startLoading();

        succeed(() => stopLoading());
        fail(() => stopLoading());
    });
});

// Leaving the page (login, logout, …) is a request too.
window.addEventListener('beforeunload', startLoading);
window.addEventListener('pageshow', stopLoading);

/*
 | Copy to clipboard
 |
 | The invitation link is copied with this helper. navigator.clipboard only works
 | on a secure origin (https or localhost); the app is often opened over the local
 | network, so an insecure origin falls back to selecting a hidden field and using
 | the old copy command. Returns whether the text ended up in the clipboard.
 */
window.copyToClipboard = function (text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text).then(
            () => true,
            () => copyWithSelection(text)
        );
    }

    return Promise.resolve(copyWithSelection(text));
};

function copyWithSelection(text) {
    const field = document.createElement('textarea');

    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.top = '-1000px';
    field.style.opacity = '0';

    document.body.appendChild(field);
    field.select();

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch (error) {
        copied = false;
    }

    document.body.removeChild(field);

    return copied;
}

/*
 | Colour scheme
 |
 | The button in the header (see components/theme-toggle.blade.php) switches the
 | class "dark" on <html> and remembers the choice, so the next visit starts with
 | the same scheme. The class itself is applied before the first paint, see
 | components/theme-script.blade.php - both files use the same storage key.
 |
 | A navigation with wire:navigate swaps the page without a reload and takes the
 | attributes of <html> from the answer of the server. That answer cannot carry
 | the class "dark", because the server does not know the choice, so the night
 | mode would be gone after every tap on a link. The stored scheme is therefore
 | painted again as soon as the new page stands. Nothing else ever changes the
 | scheme - only the button does.
 */
const COLOR_SCHEME_KEY = 'color-scheme';

/** The choice of this tab, used when the browser refuses to hand out its storage. */
let colorScheme = null;

/**
 * The scheme the user picked ("dark" or "light"), or null while nothing has been
 * chosen. Without a choice the light default of the app stays: the setting of
 * the operating system is deliberately ignored, so the app never switches on
 * its own - not even overnight.
 */
function storedColorScheme() {
    try {
        const stored = localStorage.getItem(COLOR_SCHEME_KEY);

        if (stored === 'dark' || stored === 'light') {
            return stored;
        }
    } catch (error) {
        // Storage is blocked (private mode) - the choice of this tab is all there is.
    }

    return colorScheme;
}

/**
 * Paints the stored scheme on <html>. This runs after every navigation within
 * the app, because the swap of the page drops the class (see above).
 */
function applyStoredColorScheme() {
    const stored = storedColorScheme();

    if (stored === null) {
        return;
    }

    document.documentElement.classList.toggle('dark', stored === 'dark');
}

document.addEventListener('livewire:navigated', applyStoredColorScheme);

window.toggleColorScheme = function () {
    const root = document.documentElement;
    const dark = !root.classList.contains('dark');

    root.classList.toggle('dark', dark);

    colorScheme = dark ? 'dark' : 'light';

    try {
        localStorage.setItem(COLOR_SCHEME_KEY, colorScheme);
    } catch (error) {
        // Private mode without storage: the choice of this tab carries it.
    }

    return dark;
};

/*
 | The installed app
 |
 | Coolventory can be added to the home screen and then runs without the bar of
 | the browser (public/manifest.json, display "standalone"). Three things are
 | wired up here:
 |
 | - The service worker (public/sw.js). It makes the app installable at all -
 |   Chrome, Edge and Android only offer the installation when a worker with a
 |   fetch handler is registered - and it answers a navigation without network
 |   with the offline page. "updateViaCache: none" keeps a deployment from
 |   waiting for the HTTP cache of the browser: the worker is read from the
 |   server on every visit, and a new one takes over right away.
 | - The language of the last page. The offline page is a static file and cannot
 |   ask the server which language the app speaks, so the language of <html>
 |   (written by every layout) is remembered for it.
 | - The installation section of the profile
 |   (resources/views/livewire/profile/profile-page.blade.php). Chrome, Edge and
 |   Android hand out an installation through beforeinstallprompt; Safari on the
 |   iPhone knows no such event and needs the way over the share menu. The
 |   section stays hidden until one of the two applies - on a desktop browser,
 |   and inside an app that is already installed, it never appears.
 */

const APP_LOCALE_KEY = 'app-locale';

/**
 * The language of the page that is standing. Every layout writes it into the
 * lang attribute of <html>, so this works for the app, the sign in screens and
 * the legal pages alike.
 */
function rememberAppLocale() {
    const locale = document.documentElement.lang.slice(0, 2).toLowerCase();

    if (locale === '') {
        return;
    }

    try {
        localStorage.setItem(APP_LOCALE_KEY, locale);
    } catch (error) {
        // Private mode without storage: the offline page falls back to the
        // language of the browser.
    }
}

document.addEventListener('livewire:navigated', rememberAppLocale);
rememberAppLocale();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).catch(() => {
            // No secure origin (an address in the local network is served over
            // http) or the worker is switched off: the app keeps working, it
            // just cannot be installed.
        });
    });
}

/** The installation the browser offered, held on to until the profile asks for it. */
let installPrompt = null;

/** Whether the app already runs from the home screen. */
function appIsInstalled() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

/** An iPhone or an iPad: the only place where the share menu is the way to install. */
function isAppleMobile() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

/**
 * Shows the installation section of the profile when it applies: the button
 * when the browser offers an installation, the hint about the share menu on an
 * iPhone, nothing at all otherwise. The section carries wire:ignore, so
 * Livewire leaves it alone and this state survives every update of the page.
 */
function showInstallHint() {
    const section = document.querySelector('[data-install-section]');

    if (section === null || appIsInstalled()) {
        return;
    }

    const button = section.querySelector('[data-install-button]');
    const hint = section.querySelector('[data-install-ios]');

    section.hidden = false;

    if (button !== null) {
        button.hidden = installPrompt === null;
    }

    if (hint !== null) {
        hint.hidden = installPrompt !== null || !isAppleMobile();
    }
}

window.addEventListener('beforeinstallprompt', (event) => {
    // Without this, Chrome would show a banner of its own; the app asks when the
    // user is on the profile page and wants it.
    event.preventDefault();

    installPrompt = event;

    showInstallHint();
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;

    const section = document.querySelector('[data-install-section]');

    if (section !== null) {
        section.hidden = true;
    }
});

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const button = event.target.closest('[data-install-button]');

    if (button === null || installPrompt === null) {
        return;
    }

    // The offer of the browser can only be used once.
    const prompt = installPrompt;

    installPrompt = null;
    button.hidden = true;

    prompt.prompt();
});

document.addEventListener('livewire:navigated', showInstallHint);
showInstallHint();

/*
 | The offline mode
 |
 | The basement has no reception, so the inventory has to stay editable without
 | a network. The surface for that is the offline page (public/offline.html): a
 | static file that knows nothing about the account and therefore keeps
 | everything it needs in the local storage of the browser:
 |
 | - "offline-snapshot" is the inventory as data (freezers, drawers and items
 |   with their best before dates), read from /offline/snapshot.
 | - "offline-queue" collects the changes that were made without a network. The
 |   next visit of a page of the app (or the offline page itself, as soon as it
 |   is online again) hands them to /offline/sync.
 | - "csrf-token" is the token of the standing session: the offline page has no
 |   page of the app around it that could carry it.
 |
 | Both directions run through here, so the copy on the device is the one of the
 | last visit: a page of the app that was loaded online takes care of it, and a
 | queue that is waiting is sent before anything else. Nothing is written while
 | a change is waiting - the sync answers with the fresh inventory anyway, and a
 | snapshot taken in between would forget what the queue is about to change.
 */

const OFFLINE_SNAPSHOT_KEY = 'offline-snapshot';
const OFFLINE_QUEUE_KEY = 'offline-queue';
const CSRF_TOKEN_KEY = 'csrf-token';

/** The note in the app frame; its presence marks a page of the app. */
const OFFLINE_NOTICE_SELECTOR = '[data-offline-notice]';

function readStorage(key) {
    try {
        return localStorage.getItem(key);
    } catch (error) {
        // Storage is blocked (private mode): there is no copy to keep.
        return null;
    }
}

function writeStorage(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch (error) {
        // Private mode without storage: the app keeps working online.
    }
}

function removeStorage(key) {
    try {
        localStorage.removeItem(key);
    } catch (error) {
        // Nothing to remove.
    }
}

/** The changes the offline page collected and nobody has sent yet. */
function offlineQueue() {
    const raw = readStorage(OFFLINE_QUEUE_KEY);

    if (raw === null) {
        return [];
    }

    try {
        const queue = JSON.parse(raw);

        return Array.isArray(queue) ? queue : [];
    } catch (error) {
        return [];
    }
}

/**
 * Drops the copy of the inventory and the changes of the account that just
 * left. The sign in pages say so with a meta tag
 * (resources/views/layouts/auth.blade.php); a page of the app never carries
 * it, so nobody loses anything by simply walking through the app.
 */
function forgetOfflineCopy() {
    removeStorage(OFFLINE_SNAPSHOT_KEY);
    removeStorage(OFFLINE_QUEUE_KEY);
    removeStorage(CSRF_TOKEN_KEY);
}

/** The token of the standing session, for the sync of the offline page. */
function rememberCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    if (meta === null) {
        return;
    }

    writeStorage(CSRF_TOKEN_KEY, meta.getAttribute('content'));
}

/**
 * The inventory as data, as the offline page needs it. Only for a visit
 * without a waiting change (see above); a failure - no network, an expired
 * session - leaves the copy as it is.
 */
async function refreshOfflineSnapshot() {
    try {
        const response = await fetch('/offline/snapshot', {
            headers: { Accept: 'application/json' },
            // An answer from the HTTP cache would be a copy of a copy.
            cache: 'no-store',
        });

        if (!response.ok) {
            return;
        }

        writeStorage(OFFLINE_SNAPSHOT_KEY, JSON.stringify(await response.json()));
    } catch (error) {
        // No network: the copy of the last visit has to do.
    }
}

/**
 * Hands the waiting changes to the server. The answer carries the inventory as
 * it stands afterwards, so it replaces the copy on the device and the queue is
 * empty once the call succeeded. Anything that fails - no network, an expired
 * session - keeps both, so no change is ever lost by trying.
 */
async function syncOfflineChanges() {
    const queue = offlineQueue();

    if (queue.length === 0) {
        return;
    }

    const token = readStorage(CSRF_TOKEN_KEY);

    try {
        const response = await fetch('/offline/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token === null ? '' : token,
            },
            body: JSON.stringify({ operations: queue }),
        });

        if (!response.ok) {
            return;
        }

        const answer = await response.json();

        if (answer.snapshot !== undefined) {
            writeStorage(OFFLINE_SNAPSHOT_KEY, JSON.stringify(answer.snapshot));
        }

        removeStorage(OFFLINE_QUEUE_KEY);

        // The list on the screen was drawn before these changes arrived, so
        // every component that shows items reads it again (see the #[On]
        // listener of the Livewire components).
        if (answer.applied > 0 && window.Livewire !== undefined) {
            Livewire.dispatch('offline-synced');
        }
    } catch (error) {
        // Still no network: the queue waits for the next attempt.
    }
}

/**
 * The note in the app frame (components/offline-notice.blade.php) is the way
 * into the offline page while the device reports that it has no network. The
 * page itself came from the server, so it cannot know it on its own.
 */
function showOfflineNotice() {
    const notice = document.querySelector(OFFLINE_NOTICE_SELECTOR);

    if (notice === null) {
        return;
    }

    notice.hidden = navigator.onLine;
}

/**
 * Takes care of the copy of the inventory. Only a page of the app carries the
 * note (and with it an inventory); the sign in screens and the legal pages
 * have nothing to keep, and their request would only end on the sign in page.
 */
function careForOfflineCopy() {
    if (document.querySelector(OFFLINE_NOTICE_SELECTOR) === null) {
        return;
    }

    rememberCsrfToken();

    // A device without a network would only collect a failed request.
    if (!navigator.onLine) {
        return;
    }

    if (offlineQueue().length > 0) {
        syncOfflineChanges();

        return;
    }

    refreshOfflineSnapshot();
}

// Nobody is signed in on these pages, so the copy of the inventory goes with
// the account that just left.
if (document.querySelector('meta[name="offline-clear"]') !== null) {
    forgetOfflineCopy();
}

document.addEventListener('livewire:navigated', showOfflineNotice);
document.addEventListener('livewire:navigated', careForOfflineCopy);

// The network comes back while a page of the app stands: the queue goes out on
// its own, without the user having to do anything.
window.addEventListener('online', showOfflineNotice);
window.addEventListener('online', careForOfflineCopy);
window.addEventListener('offline', showOfflineNotice);

showOfflineNotice();
careForOfflineCopy();


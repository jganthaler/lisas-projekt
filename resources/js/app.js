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

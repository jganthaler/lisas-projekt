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
 | components/theme-script.blade.php.
 */
window.toggleColorScheme = function () {
    const root = document.documentElement;
    const dark = !root.classList.contains('dark');

    root.classList.toggle('dark', dark);

    try {
        localStorage.setItem('color-scheme', dark ? 'dark' : 'light');
    } catch (error) {
        // Private mode without storage: the choice lasts for this page only.
    }

    return dark;
};

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

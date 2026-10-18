<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.

## Conventions

- Every delete confirmation asks through the dialog of the app
  (`resources/views/components/confirm-dialog.blade.php`, `<x-confirm-dialog>`),
  never through `wire:confirm` or `window.confirm()`. The button that starts the
  action only dispatches `confirm-dialog` with a unique name; the action
  (`wire:click`) sits on the confirm button inside the dialog, so the answer and
  the request travel together. Texts live under `<area>.delete_confirm_title` and
  `<area>.delete_confirm_message`. `tests/Feature/DeleteConfirmationTest.php`
  guards this rule.
- Changing the language answers with a page load (a Livewire redirect with
  `navigate: true`, see `ProfilePage::setLocale()`), never with an in-place
  update: the app frame in `resources/views/layouts/app.blade.php` - header and
  the bottom bar with "Gefrierschrank", "Inventar" and "MHD" - sits outside the
  Livewire components and would keep the old language otherwise.
  `tests/Feature/LanguageTest.php` guards this rule.
- The app has one picture: `public/favicon.png` (512 x 512, the snowflake in the
  brand colour). The tab (`rel="icon"`), the home screen of iOS
  (`rel="apple-touch-icon"`) and the manifest of the PWA
  (`public/manifest.json`) all point at that file, and every layout links them
  through `resources/views/components/app-icons.blade.php` (`<x-app-icons />`).
  Never add a second icon file. `tests/Feature/PwaTest.php` guards this rule.
- The app is a PWA. `public/sw.js` is the only place that decides what comes
  from the cache: pages of the app never do - the items and their best before
  dates live on the server, and a stale page would be worse than none - while
  the built files under `public/build`, the icon, the manifest and the offline
  page `public/offline.html` always do. A navigation without network is answered
  with that offline page; it is a static file that carries its own styles, its
  own script and the three languages of the app (`app-locale` and
  `color-scheme` are the keys the app itself writes). Raise `VERSION` in
  `public/sw.js` whenever the offline page, the icon or the manifest changes,
  otherwise the phones keep the old copy. The installation is offered in the
  profile (`resources/views/livewire/profile/profile-page.blade.php`): the
  section stays hidden until `resources/js/app.js` reveals it, and it carries
  `wire:ignore` so a Livewire update cannot hide it again.
  `tests/Feature/PwaTest.php` guards these rules.
</laravel-boost-guidelines>

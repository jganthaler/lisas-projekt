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
- A list that stands outside a drawer (the inventory, the MHD watch list) leads
  into that drawer with `?item=<id>`: the name of the drawer in the row is the
  link (`resources/views/components/item-row.blade.php`) and
  `DrawerPage::mount()` opens the form of that item - `&move=1` next to it opens
  the move form instead, which is what the "move" entry of the menu of the row
  uses. An id from another drawer or an invented one changes nothing.
  `tests/Feature/DrawerPageTest.php` guards this rule.
- A row of a list outside a drawer has three lines - the name, the best before
  date as "MHD: <date>" and the place as "<freezer>, <drawer>" - on a white card,
  with the quantity and the menu (⋮: edit, delete, move) on the right
  (`resources/views/components/item-row.blade.php`). Editing and moving are the
  functions of the drawer, so both entries of the menu lead into it with the
  item; the category of an item serves the filter of the inventory, it is never
  a colour of a row. `tests/Feature/InventoryOverviewTest.php` and
  `tests/Feature/ExpiringItemsTest.php` guard this rule.
- "−" on the last piece of an item never does nothing: the app knows no
  quantity of zero, so the row offers its delete question (the dialog of the
  app, `InteractsWithItems::decrementQuantity()`) and only the answer inside it
  deletes the item. The offline page asks the same way - with its own sheet
  (`public/offline.html`, `changeQuantity`). `tests/Feature/DrawerPageTest.php`
  guards this rule.
- Changing the language answers with a page load (a Livewire redirect with
  `navigate: true`, see `ProfilePage::setLocale()`), never with an in-place
  update: the app frame in `resources/views/layouts/app.blade.php` - header and
  the bottom bar with "Gefrierschrank", "Inventar" and "MHD" - sits outside the
  Livewire components and would keep the old language otherwise.
  `tests/Feature/LanguageTest.php` guards this rule.
- The profile picture is stored the moment it is chosen, and its answer is a page
  load as well (a Livewire redirect with `navigate: true`, see
  `ProfilePage::updatedAvatar()` and `ProfilePage::removeAvatar()`), for the very
  reason the language switch reloads: the avatar in the header sits in
  `resources/views/layouts/app.blade.php` outside the Livewire components and
  would keep the old picture otherwise (the initials after the first upload, the
  deleted picture after a removal). Every page reads the address from the account
  (`User::avatarUrl()`, with a version of the stored path, so a new picture is a
  new address on every device), `/avatars/{user}` streams the file with the type
  its extension names (`App\Support\AvatarImage::mimeTypeOf()`; without a type a
  phone shows no picture), and `resources/views/components/avatar.blade.php`
  always carries the initials behind the picture: when the picture cannot be
  loaded, `onerror` drops it and shows them instead of the broken image of the
  browser, without touching the stored file.
  `tests/Feature/ProfileTest.php` and `tests/Feature/AvatarDeliveryTest.php`
  guard these rules.
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
- The offline page is the inventory itself, because the cellar has no reception:
  items have to be editable without a network. The copy travels through the
  local storage of the browser - `resources/js/app.js` writes the snapshot of
  `GET /offline/snapshot` into `offline-snapshot` (and the token of the session
  into `csrf-token`), the offline page collects its changes in `offline-queue`
  and hands them to `POST /offline/sync` as soon as there is a network again.
  `app/Support/OfflineInventory.php` builds the snapshot and applies the
  operations (one per item, addressed by its `uuid`; the last change wins, a
  forbidden one is skipped instead of failing the batch) and
  `app/Http/Controllers/OfflineController.php` answers both addresses. Every
  screen that lists items listens for `offline-synced`
  (`app/Livewire/Concerns/FollowsOfflineChanges.php`), so a page that stood open
  does not keep a list that is older than the database. Deleting asks through
  the sheet of the offline page, never through `window.confirm()`.
  `tests/Feature/OfflineTest.php` and `tests/Feature/PwaTest.php` guard this.
</laravel-boost-guidelines>

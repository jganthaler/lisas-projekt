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
  into that drawer with `?item=<id>`: the "edit" and "move" entries of the menu
  of the row (`resources/views/components/item-row.blade.php`) carry the item,
  and `DrawerPage::mount()` opens the form of that item - `&move=1` next to it
  opens the move form instead. The place of the row itself is plain text: the
  name of the drawer is no link. An id from another drawer or an invented one
  changes nothing. `tests/Feature/DrawerPageTest.php` guards this rule.
- A row of a list outside a drawer has three lines - the name, the best before
  date as "MHD: <date>" and the place as "<freezer>, <drawer>" - on a white card,
  with the quantity and the menu (⋮: edit, delete, move) on the right
  (`resources/views/components/item-row.blade.php`). Both names of the place are
  plain text of the same size and colour, with the comma between them; the way
  into the drawer is the menu, never the name of the drawer. Editing and moving
  are the functions of the drawer, so both entries of the menu lead into it with
  the item; the category of an item serves the filter of the inventory, it is
  never a colour of a row. The second line stays with the date: only the
  inventory reads the row with `:mhd-warnings-only="true"` and keeps the state
  of the best before date while it warns, the MHD watch list names the state in
  the headings of its sections instead, and the relative counter ("MHD in 3
  Tagen") stands in no row of the app. `tests/Feature/InventoryOverviewTest.php`
  and `tests/Feature/ExpiringItemsTest.php` guard this rule.
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
- Every card of the app has the same corners: 8 px, defined once. The value is
  the token `--radius-card` in the `@theme` block of `resources/css/app.css`
  (Tailwind turns it into `rounded-card`, `rounded-t-card` and the other sides),
  and the component classes that paint a card - `.card`, `.drawer-row` (the row
  of a drawer) and `.highlight-item` (the flash on a freshly added item) - take
  their `border-radius` from it. A view never rounds a card itself: it carries
  `card` (or `drawer-row`), so a card that is written tomorrow looks like the
  ones of today; whoever needs one side of it takes the same value
  (`rounded-t-card` for the band of a drawer row). `--radius-control` stays for
  buttons and fields, a card is neither. The offline page
  (`public/offline.html`) draws the inventory without the CSS of the app and
  repeats the value in its own styles. `tests/Feature/CardRadiusTest.php` guards
  these rules.
- The area under the hairline of the freezer field
  (`resources/views/livewire/freezer/closed-freezer.blade.php`) carries the very
  tone of the block of the active freezer below it - `bg-brand-50`, in the dark
  scheme `bg-brand-950` -, so the field reads as one band, not as a block on a
  white card. Both places name the same class of the palette of the app, never a
  value of their own. The band runs over the full width of the field (the card
  brings no padding of its own) and takes the bottom corners of the card
  (`rounded-b-card`, the `--radius-card` of `resources/css/app.css`) while no
  form stands under it. `tests/Feature/ClosedFreezerTest.php` guards this rule.
- The button next to the search field of the inventory
  (`resources/views/livewire/inventory-overview.blade.php`) is a square: as wide
  as it is high - `h-11 w-11`, the square size of the app - and it sits centred
  (`self-center`), because the field beside it is a little taller than the
  square. A flex row stretches its items by default, so a stretched button would
  be a rectangle. `tests/Feature/InventoryOverviewTest.php` guards this rule.
- The head of a row of the home page carries its icon in the same square,
  whatever the row belongs to: the tile of the active freezer
  (`resources/views/livewire/freezer/closed-freezer.blade.php`) and the tile of a
  drawer below it are both `h-10 w-10` and carry a `h-5 w-5` glyph, so the two
  icons stand symmetrically one below the other. Only the tone tells them apart:
  the tile of the freezer is filled with the brand colour (`bg-brand-600
  text-white`) and marks the active freezer, the tile of a drawer stays light
  (`bg-brand-50 text-brand-600`). `tests/Feature/ClosedFreezerTest.php` guards
  this rule.
- Every row that offers a cancel and an action ends the same way, everywhere in
  the app: "Abbrechen" (`common.cancel`) on the left and the primary action on
  the right - "Speichern" (`common.save`) while something is being changed,
  "Erstellen" (`common.create`) while a freezer is being added, "Verschieben"
  (`drawers.move_submit`) while an item changes its place -, and both are
  exactly the same size. The row is a grid of two equal columns
  (`grid grid-cols-2 gap-2`, every button `w-full`), never a flex row with
  `flex-1` on one of them: there the width would follow the length of the label,
  and "Abbrechen" and "Speichern" are not the same word. The order in the markup
  is the order on the screen, and the row looks the same on a phone and on a big
  screen; a lone "Abbrechen" (a household without a second drawer) keeps the
  whole width (`col-span-2`). The forms of the freezers and the drawers
  (`resources/views/livewire/freezer/closed-freezer.blade.php`,
  `open-freezer.blade.php`) carry the pair as well, and the offline page carries
  it in its sheet (`public/offline.html`, `.actions-pair`). Only the question
  before a delete keeps its own row: the buttons of the dialog stand one below
  the other on a phone and side by side from `sm` on, in reverse order, so the
  answer sits on the right there too
  (`resources/views/components/confirm-dialog.blade.php`).
  `tests/Feature/ActionPairTest.php` guards the rule over every view,
  `tests/Feature/DrawerPageTest.php` and `tests/Feature/PwaTest.php` the two
  forms of an item and the sheet of the offline page.
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

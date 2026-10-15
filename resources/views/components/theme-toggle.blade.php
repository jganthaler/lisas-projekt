{{--
 | Switches between the light and the dark scheme (see resources/js/app.js).
 |
 | Both icons live in the markup and the dark variant decides which one is shown,
 | so the icon follows the scheme without any JavaScript state. Extra classes of
 | the caller - for example the position on the login screen - are merged in
 | through $attributes.
--}}
<button type="button" {{ $attributes->class(['btn', 'btn-ghost', 'btn-icon']) }}
        onclick="window.toggleColorScheme()"
        aria-label="{{ __('nav.theme_toggle') }}" title="{{ __('nav.theme_toggle') }}">
    <x-icon name="moon" class="h-5 w-5 dark:hidden" />
    <x-icon name="sun" class="hidden h-5 w-5 dark:block" />
</button>

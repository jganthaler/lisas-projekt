{{--
 | Applies the saved colour scheme before the page is painted, so dark mode never
 | flashes white. This has to be a small, blocking script in <head>; everything
 | else (the toggle in the header) lives in resources/js/app.js.
 |
 | The light scheme (day mode) is the default: only a saved choice switches the
 | page to dark, the setting of the operating system is deliberately ignored.
--}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('color-scheme');

            if (stored === 'dark' || stored === 'light') {
                document.documentElement.classList.toggle('dark', stored === 'dark');
            }
        } catch (error) {
            // Storage is blocked (private mode) - the light default stays.
        }
    })();
</script>

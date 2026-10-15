{{--
 | Applies the saved colour scheme before the page is painted, so dark mode never
 | flashes white. This has to be a small, blocking script in <head>; everything
 | else (the toggle in the header) lives in resources/js/app.js.
 |
 | Without a saved choice the setting of the operating system decides.
--}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('color-scheme');

            if (stored === 'dark' || stored === 'light') {
                document.documentElement.classList.toggle('dark', stored === 'dark');

                return;
            }
        } catch (error) {
            // Storage is blocked (private mode) - fall back to the system setting.
        }

        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>

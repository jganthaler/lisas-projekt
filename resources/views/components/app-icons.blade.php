{{--
 | The icon of the app and the manifest of the installed version. Every layout
 | includes this component, so the tab, the home screen and the installed app
 | always carry the same picture.
 |
 | There is exactly one source for the artwork: public/favicon.png (512 x 512,
 | the snowflake in the brand colour, drawn edge to edge). The tab
 | (rel="icon"), the home screen of iOS (rel="apple-touch-icon") and the
 | manifest of the PWA all point at that file, so the icon can never drift
 | apart. The manifest itself is a static file (public/manifest.json) and needs
 | no route - ".json" is a MIME type every web server already knows, while
 | ".webmanifest" would have to be taught to it first.
 |
 | tests/Feature/PwaTest.php keeps the three links and the artwork in place.
--}}
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('favicon.png') }}">
<link rel="apple-touch-icon" sizes="512x512" href="{{ asset('favicon.png') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">

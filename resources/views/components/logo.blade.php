{{--
 | The logo of the app: the file public/logo.svg - the two snowflakes next to
 | the word mark "coolventory".
 |
 | The height is measured in "em", so whoever uses the logo decides how big it
 | gets by passing a text size class ("text-base" in the header, "text-2xl" on
 | the login screen).
 |
 | The mark of the file is drawn for a light background - its "ventory" is
 | almost black. Every page of the app therefore carries it on a light surface
 | (the grey of frost-100 and the white cards); in the dark scheme the file is
 | turned into a white mark, because it would disappear on the dark page
 | otherwise.
--}}

{{-- The image carries the name of the app as its accessible name, so it is read
     out as "Coolventory" instead of as a file name. --}}
<img src="{{ asset('logo.svg') }}" alt="Coolventory" aria-label="Coolventory"
     {{ $attributes->class(['inline-block h-[1.6em] w-auto dark:brightness-0 dark:invert']) }}>

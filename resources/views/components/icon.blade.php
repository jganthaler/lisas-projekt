@props(['name' => 'dot'])

@php
    /*
     | A tiny, hand drawn icon set. Every icon lives on the same 24x24 grid and
     | inherits the text colour, so it can be used next to any label.
     */
    $icons = [
        'freezer' => '<rect x="6.5" y="3" width="11" height="18" rx="2.5"/><path d="M6.5 9.5h11"/><path d="M10.5 5.5v2"/><path d="M10.5 12v2"/>',
        'list' => '<path d="M9 6h10"/><path d="M9 12h10"/><path d="M9 18h10"/><path d="M4.5 6h.01"/><path d="M4.5 12h.01"/><path d="M4.5 18h.01"/>',
        'alert' => '<path d="M12 4.5 21 20H3z"/><path d="M12 10v4"/><path d="M12 17h.01"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'check' => '<path d="M5 13l4.5 4.5L19 6.5"/>',
        'close' => '<path d="M6 6l12 12"/><path d="M18 6 6 18"/>',
        'pencil' => '<path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16z"/><path d="M13.5 6.5 17.5 10.5"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M6.5 7l1 13h9l1-13"/><path d="M10.5 11v6"/><path d="M13.5 11v6"/>',
        'chevron-right' => '<path d="M9.5 5.5 16 12l-6.5 6.5"/>',
        'chevron-down' => '<path d="M5.5 9.5 12 16l6.5-6.5"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="M11 6 5 12l6 6"/>',
        'share' => '<circle cx="17.5" cy="6" r="2.8"/><circle cx="6" cy="12" r="2.8"/><circle cx="17.5" cy="18" r="2.8"/><path d="M8.5 10.6 15 7.4"/><path d="M8.5 13.4 15 16.6"/>',
        'link' => '<path d="M10 14a4 4 0 0 1 0-5.7l2.3-2.3a4 4 0 0 1 5.7 5.7L16.5 13"/><path d="M14 10a4 4 0 0 1 0 5.7l-2.3 2.3a4 4 0 0 1-5.7-5.7L7.5 11"/>',
        'copy' => '<rect x="9" y="9" width="11" height="11" rx="2.5"/><path d="M15 9V6.5A2.5 2.5 0 0 0 12.5 4H6.5A2.5 2.5 0 0 0 4 6.5v6A2.5 2.5 0 0 0 6.5 15H9"/>',
        'camera' => '<path d="M4 8.5h3l1.5-2h7L17 8.5h3v10H4z"/><circle cx="12" cy="13.5" r="3.2"/>',
        'logout' => '<path d="M15 5.5h3.5v13H15"/><path d="M11 8.5 14.5 12 11 15.5"/><path d="M14 12H4"/>',
        'user' => '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c1.2-3.3 3.9-5 7-5s5.8 1.7 7 5"/>',
        'snowflake' => '<path d="M12 3v18"/><path d="M4.2 7.5 19.8 16.5"/><path d="M19.8 7.5 4.2 16.5"/>',
        'door' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M14.5 12h.01"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
        'filter' => '<path d="M20.5 4.5h-17L10 12v6l4 2v-8z"/>',
        'dot' => '<circle cx="12" cy="12" r="3.2"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2"/><path d="M12 19v2"/><path d="M3 12h2"/><path d="M19 12h2"/><path d="M5.6 5.6 7 7"/><path d="M17 17l1.4 1.4"/><path d="M18.4 5.6 17 7"/><path d="M7 17l-1.4 1.4"/>',
        'moon' => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"/>',
    ];
@endphp

<svg {{ $attributes }} width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {!! $icons[$name] ?? $icons['dot'] !!}
</svg>

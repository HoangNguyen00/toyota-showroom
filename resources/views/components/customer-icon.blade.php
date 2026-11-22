@props(['name'])
@php
    $paths = [
        'grid' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'chat' => 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-2 2V11.5A8.5 8.5 0 0 1 10.5 3h2a8.5 8.5 0 0 1 8.5 8.5Z M7 10h10 M7 14h6',
        'car' => 'm3 10 2-6h14l2 6 M2 10h20v9h-3v-3H5v3H2z M5 12h3 M16 12h3',
        'arrow' => 'M5 12h14 M13 6l6 6-6 6',
        'back' => 'M19 12H5 M11 6l-6 6 6 6',
        'calendar' => 'M4 5h16v16H4z M8 2v6 M16 2v6 M4 10h16 M8 14h3',
        'logout' => 'M10 3H4v18h6 M9 12h12 M16 7l5 5-5 5',
        'search' => 'M21 21l-6-6 M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z',
        'send' => 'm22 2-7 20-4-9-9-4 20-7Z M22 2 11 13',
        'shield' => 'm12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6l8-4Z M8 12l3 3 5-6',
    ];
@endphp
<svg {{ $attributes->class(['customer-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['arrow'] }}"/></svg>

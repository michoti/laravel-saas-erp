{{-- 20 custom stroke icons. Usage: <x-ui.icon name="cart" class="size-5" /> --}}
@props(['name'])
@php
$icons = [
 'cart' => ['M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h8.2a1 1 0 0 0 1-.7L20 8H6','M9.5 20h.01','M17.5 20h.01'],
 'users' => ['M16 20v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1','M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7','M20 20v-1a4 4 0 0 0-3-3.8','M16 4.3a3.5 3.5 0 0 1 0 6.4'],
 'box' => ['M21 8l-9-5-9 5 9 5 9-5z','M3 8v8l9 5 9-5V8','M12 13v8'],
 'chart' => ['M4 20V10','M10 20V4','M16 20v-7','M22 20H2'],
 'receipt' => ['M6 3h12v18l-3-2-3 2-3-2-3 2V3z','M9 8h6','M9 12h6'],
 'wallet' => ['M3 7a2 2 0 0 1 2-2h13v4','M3 7v11a2 2 0 0 0 2 2h15V9H5a2 2 0 0 1-2-2z','M16 14.5h.01'],
 'shield' => ['M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6l8-3z','M9 12l2 2 4-4'],
 'layers' => ['M12 3l9 5-9 5-9-5 9-5z','M3 13l9 5 9-5'],
 'bolt' => ['M13 2L4 14h7l-1 8 9-12h-7l1-8z'],
 'check' => ['M5 12.5l4.5 4.5L19 7'],
 'arrow-right' => ['M5 12h14','M13 6l6 6-6 6'],
 'menu' => ['M4 7h16','M4 12h16','M4 17h16'],
 'x' => ['M6 6l12 12','M18 6L6 18'],
 'chevron-down' => ['M6 9l6 6 6-6'],
 'search' => ['M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z','M20 20l-4-4'],
 'phone' => ['M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z'],
 'mail' => ['M4 6h16v12H4z','M4 7l8 6 8-6'],
 'map-pin' => ['M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11z','M12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z'],
 'sparkle' => ['M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z','M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7.7-2z'],
 'book' => ['M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5z','M4 21h15'],
];
@endphp
<svg {{ $attributes->merge(['class' => 'size-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @foreach ($icons[$name] ?? [] as $d)<path d="{{ $d }}" />@endforeach
</svg>

{{-- Colourful uploaded SVG artwork (public/images/art/*.svg). Loaded as a cached <img> so pages stay light. --}}
@props(['name', 'size' => 64])
<img src="{{ asset('images/art/'.$name.'.svg') }}" alt="" role="presentation" width="{{ $size }}" height="{{ $size }}" loading="lazy" decoding="async" {{ $attributes->class('shrink-0 select-none') }}>

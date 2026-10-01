{{-- Lazy image with skeleton placeholder, fixed aspect ratio (no layout shift). eager=true for the LCP image. --}}
@props(['src', 'alt', 'width' => 800, 'height' => 600, 'eager' => false])
<div x-data="{ ok: false }" x-init="ok = $refs.i.complete" x-bind:class="ok || 'animate-pulse'" {{ $attributes->class('overflow-hidden bg-primary-soft') }}>
    <img x-ref="i" x-on:load="ok = true" src="{{ asset('images/art/'.$src.'.svg') }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}" @if ($eager) fetchpriority="high" @endif decoding="async"
         class="h-full w-full object-cover transition-opacity duration-500" x-bind:class="ok ? 'opacity-100' : 'opacity-0'">
</div>

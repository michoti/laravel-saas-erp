{{-- Trigger goes in the default slot; the preview goes in the "card" slot. Opens on hover and keyboard focus. --}}
<div x-data="{ open: false }" class="relative inline-block" x-on:mouseenter="open = true" x-on:mouseleave="open = false" x-on:focusin="open = true" x-on:focusout="open = false" x-on:keydown.escape="open = false">
    {{ $slot }}
    <div x-show="open" x-cloak x-transition.origin.bottom.duration.150ms role="tooltip"
        class="absolute bottom-full left-1/2 z-30 mb-3 w-64 -translate-x-1/2 rounded-2xl bg-ink p-4 text-sm leading-relaxed text-white shadow-soft-lg">
        {{ $card }}
    </div>
</div>

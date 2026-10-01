@props(['id', 'title'])
<div>
    <h3>
        <button type="button" id="{{ $id }}-btn" aria-controls="{{ $id }}-panel" x-bind:aria-expanded="active === '{{ $id }}'"
            x-on:click="active = active === '{{ $id }}' ? null : '{{ $id }}'"
            class="flex min-h-14 w-full items-center justify-between gap-4 px-5 py-4 text-left font-ui text-base font-semibold transition hover:bg-surface-alt sm:px-6">
            {{ $title }}
            <x-ui.icon name="chevron-down" class="shrink-0 text-primary transition duration-200" x-bind:class="active === '{{ $id }}' && 'rotate-180'" />
        </button>
    </h3>
    <div x-show="active === '{{ $id }}'" x-collapse x-cloak id="{{ $id }}-panel" role="region" aria-labelledby="{{ $id }}-btn">
        <div class="px-5 pb-5 leading-relaxed text-ink-soft sm:px-6">{{ $slot }}</div>
    </div>
</div>

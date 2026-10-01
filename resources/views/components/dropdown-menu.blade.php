{{-- <x-dropdown-menu label="Apps"> <a role="menuitem" ...> </x-dropdown-menu> --}}
@props(['label', 'align' => 'left'])
<div x-data="{ open: false }" class="relative" x-on:keydown.escape="open = false" x-on:click.outside="open = false">
    <button type="button" x-on:click="open = !open" aria-haspopup="menu" x-bind:aria-expanded="open"
        class="inline-flex min-h-12 items-center gap-1 rounded-full px-4 font-ui text-sm font-semibold text-ink transition hover:bg-primary-soft">
        {{ $label }}
        <x-ui.icon name="chevron-down" class="size-4 transition" x-bind:class="open && 'rotate-180'" />
    </button>
    <div x-show="open" x-cloak x-transition:enter="transition duration-300 ease-[cubic-bezier(.22,1,.36,1)]" x-transition:enter-start="opacity-0 -translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-effect="open && $nextTick(() => window.gf?.follow($el, .05))" role="menu"
        class="{{ $align === 'right' ? 'right-0' : 'left-0' }} absolute z-40 mt-2 w-72 rounded-2xl bg-surface p-2 shadow-soft-lg ring-1 ring-steel-soft">
        {{ $slot }}
    </div>
</div>

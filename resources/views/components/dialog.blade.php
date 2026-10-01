{{-- Bottom sheet on phones (thumb zone), centred modal from sm up.
     Open: $dispatch('open-dialog', 'demo')   Close: $dispatch('close-dialog') --}}
@props(['name', 'title'])
<div x-data="{ open: false }"
     x-on:open-dialog.window="if ($event.detail === '{{ $name }}') open = true"
     x-on:close-dialog.window="open = false"
     x-on:keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title">
        <div x-show="open" x-transition.opacity.duration.200ms class="absolute inset-0 bg-ink/50 backdrop-blur-sm" x-on:click="open = false"></div>
        <div x-show="open" x-trap.noscroll="open" x-effect="open && $nextTick(() => window.gf?.follow($refs.body))"
             x-transition:enter="transition duration-500 ease-[cubic-bezier(.22,1,.36,1)]" x-transition:enter-start="translate-y-full sm:translate-y-6 sm:scale-95 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0"
             class="relative max-h-[92dvh] w-full max-w-lg overflow-y-auto overscroll-contain rounded-t-3xl bg-surface px-5 pt-3 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-soft-lg sm:rounded-3xl sm:p-8">
            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-steel-soft sm:hidden" aria-hidden="true"></div>
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 id="{{ $name }}-title" class="text-2xl font-black">{{ $title }}</h2>
                <button type="button" data-squash x-on:click="open = false" aria-label="Close" class="grid size-12 shrink-0 place-items-center rounded-full bg-surface-alt text-ink-soft transition hover:text-ink"><x-ui.icon name="x" /></button>
            </div>
            <div x-ref="body">{{ $slot }}</div>
        </div>
    </div>
</div>

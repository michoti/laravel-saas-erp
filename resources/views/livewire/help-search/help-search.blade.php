<div>
    <div class="relative">
        <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-steel" />
        <input type="search" wire:model.live.debounce.250ms="q" placeholder="Search guides, e.g. M-Pesa, refund, stock"
               aria-label="Search the Help Center"
               class="min-h-14 w-full rounded-full border-0 bg-surface py-3 pl-12 pr-12 shadow-soft ring-1 ring-steel-soft focus:ring-2 focus:ring-primary">
        <x-spinner wire:loading wire:target="q" class="absolute right-4 top-1/2 -translate-y-1/2 text-primary" />
    </div>

    <x-scroll-area label="Help articles" class="mt-4 max-h-96 bg-surface p-2 shadow-soft ring-1 ring-steel-soft">
        @forelse ($this->results as [$title, $tag, $blurb])
            <a href="{{ route('docs') }}" wire:navigate class="flex items-start gap-4 rounded-xl p-4 transition hover:bg-primary-soft">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-soft text-primary"><x-ui.icon name="book" /></span>
                <span class="min-w-0">
                    <span class="flex flex-wrap items-center gap-2"><span class="font-ui text-sm font-semibold">{{ $title }}</span><x-badge tone="neutral">{{ $tag }}</x-badge></span>
                    <span class="mt-1 block text-sm text-ink-soft">{{ $blurb }}</span>
                </span>
            </a>
        @empty
            <div class="p-8 text-center">
                <p class="font-semibold">No guides match “{{ $q }}”.</p>
                <p class="mt-1 text-sm text-ink-soft">Try a shorter word, or ask our support team.</p>
                <x-button class="mt-4" variant="secondary" x-on:click="$dispatch('open-dialog', 'demo')">Contact us</x-button>
            </div>
        @endforelse
    </x-scroll-area>
</div>

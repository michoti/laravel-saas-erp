{{-- <x-carousel :count="3" :autoplay="7000"> slides as direct children with class "w-full shrink-0" </x-carousel> --}}
@props(['count', 'autoplay' => 0])
<div x-data="carousel({{ $count }}, {{ $autoplay }})" x-on:mouseenter="paused = true" x-on:mouseleave="paused = false" x-on:focusin="paused = true" x-on:focusout="paused = false"
     role="region" aria-roledescription="carousel" aria-label="{{ $attributes->get('aria-label', 'Highlights') }}" class="relative" x-on:touchstart.passive="swipeStart($event)" x-on:touchend.passive="swipeEnd($event)">
    <div class="overflow-hidden rounded-3xl">
        <div class="flex transition-transform duration-700 ease-[cubic-bezier(.22,1,.36,1)] motion-reduce:transition-none" x-bind:style="`transform: translateX(-${i * 100}%)`" aria-live="polite">
            {{ $slot }}
        </div>
    </div>
    <div class="mt-4 flex items-center justify-between">
        <div class="flex gap-2" role="tablist">
            @for ($n = 0; $n < $count; $n++)
                <button type="button" role="tab" x-on:click="i = {{ $n }}" aria-label="Show slide {{ $n + 1 }}"
                    class="grid h-12 place-items-center px-1"><span class="block h-2 rounded-full transition-all" x-bind:class="i === {{ $n }} ? 'w-8 bg-primary' : 'w-2 bg-steel'" style="transition: width .4s var(--ease-back), background-color .2s"></span></button>
            @endfor
        </div>
        <div class="flex gap-2">
            <button type="button" x-on:click="prev()" aria-label="Previous slide" data-squash class="grid size-12 place-items-center rounded-full bg-surface shadow-soft ring-1 ring-steel-soft transition hover:ring-steel"><x-ui.icon name="arrow-right" class="rotate-180" /></button>
            <button type="button" x-on:click="next()" aria-label="Next slide" data-squash class="grid size-12 place-items-center rounded-full bg-primary text-white shadow-soft transition hover:bg-primary-600"><x-ui.icon name="arrow-right" /></button>
        </div>
    </div>
</div>

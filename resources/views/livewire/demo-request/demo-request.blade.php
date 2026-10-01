{{-- Mobile-first: two required fields (name + phone), one-tap choices, optional extras tucked away. --}}
<div>
    @if ($sent)
        <div class="py-4 text-center">
            <x-ui.art name="check-money-order" class="mx-auto size-20" :size="80" />
            <h3 class="mt-4 text-2xl font-black">Request received</h3>
            <p class="mt-2 text-base text-ink-soft">Asante, {{ $name }}. We will call {{ $phone }} within one business day.</p>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <x-button variant="secondary" wire:click="again">Send another</x-button>
                <x-button x-on:click="$dispatch('close-dialog')">Done</x-button>
            </div>
        </div>
    @else
        <p class="mb-4 text-base text-ink-soft">Two quick details and we will call you with a starting point for your business.</p>
        <form wire:submit="submit" class="space-y-4" novalidate x-data="{ more: false }">
            <x-input label="Your name" name="name" wire:model.blur="name" autocomplete="name" enterkeyhint="next" placeholder="Wanjiru Kamau" />
            <x-input label="Phone (M-Pesa number)" name="phone" type="tel" inputmode="tel" wire:model.blur="phone" autocomplete="tel" enterkeyhint="next" placeholder="0712 345 678" />

            <fieldset>
                <legend class="mb-2 font-ui text-sm font-semibold">What do you want to start with?</legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['pos' => 'Point of Sale', 'inventory' => 'Inventory', 'crm' => 'CRM', 'not-sure' => 'Not sure yet'] as $v => $l)
                        <label class="relative">
                            <input type="radio" wire:model="interest" value="{{ $v }}" class="peer sr-only">
                            <span class="flex min-h-12 items-center justify-center rounded-2xl bg-surface-alt px-3 text-center font-ui text-sm font-semibold ring-1 ring-steel-soft transition active:scale-[.97] peer-checked:bg-primary peer-checked:text-white peer-checked:ring-primary peer-focus-visible:outline-2 peer-focus-visible:outline-primary">{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <button type="button" x-on:click="more = !more" x-bind:aria-expanded="more" class="flex min-h-12 w-full items-center justify-between rounded-2xl px-1 font-ui text-sm font-semibold text-primary-600">
                    Add email or notes (optional)
                    <x-ui.icon name="chevron-down" class="size-5 transition" x-bind:class="more && 'rotate-180'" />
                </button>
                <div x-show="more" x-collapse x-cloak class="space-y-4 pt-2">
                    <x-input label="Email" name="email" type="email" inputmode="email" wire:model.blur="email" autocomplete="email" placeholder="you@business.co.ke" />
                    <x-input label="Business name" name="company" wire:model.blur="company" autocomplete="organization" />
                    <x-textarea label="Anything we should know?" name="message" wire:model.blur="message" rows="3" placeholder="Branches, current tools, go-live date…" />
                </div>
            </div>

            <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="submit">
                <x-spinner wire:loading wire:target="submit" />
                <span wire:loading.remove wire:target="submit">Request a call back</span>
                <span wire:loading wire:target="submit">Sending…</span>
            </x-button>
        </form>
    @endif
</div>

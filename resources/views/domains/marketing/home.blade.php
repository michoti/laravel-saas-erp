<x-layouts.app title="POS, inventory and CRM software for Kenya" description="Take M-Pesa payments, track stock and manage customers in one workspace. Grafame ERP is modular business software for Kenyan retailers and growing teams.">

{{-- 1. Hero: primary action first, then proof --}}
<section class="bg-surface">
    <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 pb-14 pt-8 sm:px-6 lg:grid-cols-2 lg:gap-12 lg:pb-24 lg:pt-16">
        <div data-hero>
            <x-badge><x-ui.icon name="sparkle" class="size-4" />Karibu. Built for Kenya</x-badge>
            <h1 class="mt-4 text-[2.15rem] font-black leading-[1.1] sm:text-5xl lg:text-6xl">Run your business from one clear view.</h1>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-ink-soft sm:text-lg">Sales, stock and customers in one workspace. Take M-Pesa at the till, see what is running low, and add apps when your team is ready.</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <x-button x-on:click="$dispatch('open-dialog', 'demo')">Get started</x-button>
                <x-button variant="secondary" :href="route('apps')" wire:navigate>Explore the apps</x-button>
            </div>
            <p class="mt-4 text-base text-ink-soft">Priced in KES. Pay for the apps you use.</p>
        </div>

        <div class="relative" data-hero-visual>
            <x-ui.img src="hero-checkout" alt="A cashier at a Kenyan shop counter serving a customer who pays by phone" :eager="true" class="aspect-[4/3] rounded-3xl shadow-soft-lg" />
            <div data-chip class="absolute -top-3 right-2 flex items-center gap-2 rounded-2xl bg-surface p-2.5 pr-4 shadow-soft-lg ring-1 ring-steel-soft sm:-right-4">
                <span class="grid size-9 place-items-center rounded-full bg-emerald-500 text-white"><x-ui.icon name="check" class="size-5" /></span>
                <span class="text-sm leading-tight"><b class="block">M-Pesa confirmed</b><span class="text-ink-soft">KES 1,340</span></span>
            </div>
            <div data-chip class="absolute -bottom-4 left-2 flex items-center gap-2 rounded-2xl bg-ink p-2.5 pr-4 text-white shadow-soft-lg sm:-left-4">
                <span class="grid size-9 place-items-center rounded-full bg-primary"><x-ui.icon name="box" class="size-5" /></span>
                <span class="text-sm leading-tight"><b class="block">Low stock</b><span class="text-steel">Sugar 1kg · 6 left</span></span>
            </div>
        </div>
    </div>
</section>

{{-- 2. What you get: quick-scan grid using the uploaded artwork (2 columns on phones) --}}
<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <h2 data-reveal class="max-w-2xl text-3xl font-black sm:text-4xl">Everything the day-to-day needs</h2>
    <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
        @foreach ([
            ['buy-cart-market', 'Fast checkout', 'Cash, card and M-Pesa in one flow.'],
            ['bag-cash-currency', 'Clear payments', 'Every order shows its payment status.'],
            ['black-friday-cheap-discount', 'Promotions', 'Discounts tied to the sale they belong to.'],
            ['check-money-order', 'Refunds done right', 'Corrections follow a reviewed process.'],
            ['chart-diagram-pie', 'Sales at a glance', 'Daily totals and top products.'],
            ['chart-growth-invest', 'Grow at your pace', 'Add apps when you need them.'],
            ['clock-event-planner', 'Follow-ups', 'Never lose track of a returning customer.'],
            ['briefcase-business-case', 'Roles and access', 'Each person sees the tasks they need.'],
        ] as [$art, $t, $b])
            <article data-reveal class="rounded-3xl bg-surface p-4 shadow-soft ring-1 ring-steel-soft sm:p-6">
                <x-ui.art :name="$art" class="size-14 sm:size-16" :size="64" />
                <h3 class="mt-3 text-base font-bold sm:text-lg">{{ $t }}</h3>
                <p class="mt-1 text-base leading-relaxed text-ink-soft sm:text-base">{{ $b }}</p>
            </article>
        @endforeach
    </div>
</section>

{{-- 3. Payments band --}}
<section class="bg-surface py-14">
    <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 sm:px-6 lg:grid-cols-2 lg:gap-12">
        <x-ui.img data-reveal src="mobile-payment" alt="A phone showing a confirmed M-Pesa payment with coins around it" class="aspect-[4/3] rounded-3xl shadow-soft" />
        <div data-reveal>
            <h2 class="text-3xl font-black sm:text-4xl">Sell the way Kenya pays</h2>
            <p class="mt-3 text-base leading-relaxed text-ink-soft sm:text-lg">Send an M-Pesa prompt from the till and watch it confirm on the order. If a callback is delayed, the system checks again on its own, so nothing hangs.</p>
            <ul class="mt-5 space-y-3 text-base">
                @foreach (['STK push straight from checkout', 'Automatic reconciliation of pending payments', 'Keeps selling when the network drops, then syncs'] as $li)
                    <li class="flex items-start gap-3"><span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-primary text-white"><x-ui.icon name="check" class="size-4" /></span>{{ $li }}</li>
                @endforeach
            </ul>
            <x-button class="mt-6 w-full sm:w-auto" x-on:click="$dispatch('open-dialog', 'demo')">See it in action</x-button>
        </div>
    </div>
</section>

{{-- 4. Workflows --}}
<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div data-reveal class="max-w-2xl">
        <h2 class="text-3xl font-black sm:text-4xl">Built around real workflows</h2>
        <p class="mt-3 text-base text-ink-soft sm:text-lg">Less time reconciling notebooks, WhatsApp groups and spreadsheets. More time serving customers.</p>
    </div>
    <div class="mt-8 grid gap-4 md:grid-cols-2 md:gap-5">
        @foreach ([
            ['cart', 'Sell with confidence', 'Organise products, serve customers, record orders and capture payments. Promotions and refunds stay attached to the sale.', 'Sales and payments stay in step, even when the network drops and comes back.'],
            ['box', 'Keep products visible', 'Follow stock from one place. Ledger records show how quantities changed, and low-stock views flag what needs attention.', 'Every stock change is recorded, so you can see what changed and when.'],
            ['users', 'Give customers a home', 'Keep customer details next to the work they support. CRM follow-ups build on the same record as it rolls out.', 'One customer record shared across sales and follow-up.'],
            ['layers', 'Grow without starting over', 'Begin with the apps you need, then add more. Module access depends on your plan and configuration.', 'Start with one branch or workflow and expand deliberately.'],
        ] as [$icon, $title, $body, $tip])
            <article data-reveal class="rounded-3xl bg-surface p-5 shadow-soft ring-1 ring-steel-soft sm:p-7">
                <span class="grid size-12 place-items-center rounded-2xl bg-primary-soft text-primary"><x-ui.icon :name="$icon" class="size-6" /></span>
                <h3 class="mt-4 text-xl font-bold">{{ $title }}</h3>
                <p class="mt-2 text-base leading-relaxed text-ink-soft">{{ $body }}</p>
                <div class="mt-2">
                    <x-hover-card>
                        <button type="button" class="inline-flex min-h-12 items-center font-ui text-sm font-semibold text-primary-600 underline-offset-4 hover:underline">In practice</button>
                        <x-slot:card>{{ $tip }}</x-slot:card>
                    </x-hover-card>
                </div>
            </article>
        @endforeach
    </div>
</section>

{{-- 5. Scenarios (swipeable) --}}
<section class="bg-surface py-14">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <h2 data-reveal class="text-3xl font-black sm:text-4xl">Made for the day-to-day</h2>
        <p data-reveal class="mt-3 text-base text-ink-soft sm:text-lg">Illustrative scenarios. Swap in real customer stories when you have them.</p>
        <div class="mt-8" data-reveal>
            <x-carousel :count="3" :autoplay="8000" aria-label="Example scenarios">
                @foreach ([
                    ['shop-front', 'Neighbourhood supermarket, Nairobi', 'Cashiers take M-Pesa and cash at one till. At closing, the owner reviews the day’s orders and sees which fast movers to reorder.', 'A neighbourhood supermarket storefront with a red and white awning'],
                    ['stock-room', 'Hardware shop, Eldoret', 'Staff record incoming stock and adjustments with a reason each time, so any count difference can be traced to real movements.', 'A storeroom worker checking shelves with a tablet'],
                    ['pharmacy-counter', 'Pharmacy, Kisumu', 'Returning customers are recognised at the counter. Managers review low-stock items every morning before ordering.', 'A pharmacist behind a counter with medicine shelves'],
                ] as [$img, $who, $story, $alt])
                    <div class="w-full shrink-0">
                        <div class="overflow-hidden rounded-3xl bg-primary-soft">
                            <x-ui.img :src="$img" :alt="$alt" class="aspect-[16/10]" />
                            <div class="p-5 sm:p-8">
                                <h3 class="text-xl font-bold">{{ $who }}</h3>
                                <p class="mt-2 text-base leading-relaxed text-ink-soft">{{ $story }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </x-carousel>
        </div>
    </div>
</section>

{{-- 6. Ownership --}}
<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="grid gap-4 md:grid-cols-3 md:gap-5">
        @foreach ([
            ['shield', 'One workspace per business', 'Each organisation has its own isolated workspace, with subscriptions and modules managed for it.'],
            ['wallet', 'Clear payment status', 'See whether each order is paid, pending or refunded, whatever method was used.'],
            ['bolt', 'Roles that match the job', 'Cashiers, managers and owners each see what they need. Review access when roles change.'],
        ] as [$icon, $t, $b])
            <div data-reveal class="rounded-3xl bg-surface p-5 shadow-soft ring-1 ring-steel-soft sm:p-7">
                <x-ui.icon :name="$icon" class="size-7 text-primary" />
                <h3 class="mt-3 text-lg font-bold">{{ $t }}</h3>
                <p class="mt-2 text-base text-ink-soft">{{ $b }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- 7. CTA --}}
<section class="mx-auto max-w-6xl px-4 sm:px-6">
    <div data-reveal class="rounded-[2rem] bg-ink px-5 py-12 text-center text-white shadow-soft-lg sm:px-12">
        <h2 class="text-3xl font-black text-white sm:text-4xl">Start focused. Expand with purpose.</h2>
        <p class="mx-auto mt-3 max-w-2xl text-base text-steel sm:text-lg">Pick one workflow, prepare your records, and let your team build a routine first.</p>
        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
            <x-button x-on:click="$dispatch('open-dialog', 'demo')">Find your starting point</x-button>
            <x-button variant="secondary" :href="route('pricing')" wire:navigate>See pricing</x-button>
        </div>
    </div>
</section>
</x-layouts.app>

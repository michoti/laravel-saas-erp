<x-layouts.app title="Pricing in KES" description="Estimate your monthly Grafame ERP cost in Kenyan shillings. Choose your apps, pay for the people using them, and scale as you grow.">
<section class="bg-surface">
    <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:py-16">
        <div>
            <x-ui.art name="bag-cash-currency-2" class="size-16" :size="64" />
            <h1 class="mt-3 text-[2rem] font-black leading-tight sm:text-5xl">Simple pricing in shillings</h1>
            <p class="mt-3 text-base leading-relaxed text-ink-soft sm:text-lg">Choose your apps and pay for the people using them. Figures below are placeholders for illustration. Confirm final plans and terms with our team.</p>
        </div>
        <x-ui.img src="dashboard-laptop" alt="A laptop showing a sales dashboard with charts" class="aspect-[4/3] rounded-3xl shadow-soft" :eager="true" />
    </div>
</section>

@php $plans = [
    ['name' => 'Starter', 'apps' => 'Point of Sale', 'base' => 2500, 'included' => 3, 'extra' => 500, 'features' => ['POS with cash and M-Pesa', 'Products and customers', 'Order and refund history']],
    ['name' => 'Growth', 'apps' => 'POS + Inventory', 'base' => 6500, 'included' => 10, 'extra' => 450, 'features' => ['Everything in Starter', 'Stock ledger and low-stock views', 'Multiple staff roles'], 'popular' => true],
    ['name' => 'Scale', 'apps' => 'All available apps', 'base' => 15000, 'included' => 25, 'extra' => 400, 'features' => ['Everything in Growth', 'CRM as it becomes available', 'Guided onboarding and priority support']],
]; @endphp

<section x-data="pricingEstimator(@js($plans))" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-xl rounded-3xl bg-surface p-5 shadow-soft ring-1 ring-steel-soft">
        <x-slider label="How many staff will use it?" x-model.number="users" />
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-3">
        <template x-for="p in plans" :key="p.name">
            <article class="relative flex flex-col rounded-3xl bg-surface p-5 sm:p-5 shadow-soft ring-1 transition" :class="p.popular ? 'ring-2 ring-primary shadow-soft-lg' : 'ring-steel-soft'">
                <x-badge x-show="p.popular" class="absolute -top-3 left-7">Most chosen</x-badge>
                <h2 class="text-xl font-black" x-text="p.name"></h2>
                <p class="text-base text-ink-soft" x-text="p.apps"></p>
                <p class="mt-6"><span class="font-heading text-4xl font-black" x-text="kes(cost(p))"></span><span class="text-sm text-ink-soft"> / month</span></p>
                <p class="mt-1 text-sm text-ink-soft"><span x-text="'Includes ' + p.included + ' users, then ' + kes(p.extra) + ' each'"></span></p>
                <ul class="mt-6 flex-1 space-y-3 text-sm">
                    <template x-for="f in p.features" :key="f"><li class="flex items-start gap-3"><span class="mt-0.5 text-primary"><x-ui.icon name="check" class="size-4" /></span><span x-text="f"></span></li></template>
                </ul>
                <x-button class="mt-8" x-bind:class="p.popular ? '' : '!bg-surface !text-ink ring-1 ring-steel-soft hover:!bg-surface-alt'" x-on:click="$dispatch('open-dialog', 'demo')">Talk to us</x-button>
            </article>
        </template>
    </div>
    <p class="mt-6 text-center text-base text-ink-soft">Estimates exclude VAT and onboarding. Module access depends on plan, configuration and release stage.</p>
</section>

<section class="mx-auto max-w-3xl px-4 pb-8 sm:px-6">
    <h2 class="mb-6 text-2xl font-black">Pricing questions</h2>
    <x-accordion>
        <x-accordion.item id="p1" title="Can I change plans later?">Yes. Confirm the effect on access, billing and existing workflows with your account administrator before switching.</x-accordion.item>
        <x-accordion.item id="p2" title="Do you support M-Pesa payments at the till?">The POS app supports M-Pesa STK push with automatic reconciliation. Payment methods available to you depend on your workspace configuration.</x-accordion.item>
        <x-accordion.item id="p3" title="Can I pay for Grafame ERP with M-Pesa?">Ask our team about billing options during onboarding.</x-accordion.item>
    </x-accordion>
</section>
</x-layouts.app>

<x-layouts.app title="POS, Inventory and CRM apps" description="Explore Grafame ERP apps: Point of Sale with M-Pesa, ledger-based Inventory and Customer Relationship Management for Kenyan businesses.">
<section class="bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h1 class="max-w-3xl text-4xl font-black sm:text-5xl">Apps for the work behind your business</h1>
        <p class="mt-4 max-w-2xl text-lg text-ink-soft">Each app covers a distinct set of workflows while the platform gives you one place to manage your organisation and its access. Availability varies by plan and release.</p>
    </div>
</section>

@php $apps = [
    ['pos', 'cart', 'Point of Sale', 'Live', 'Make each transaction part of the bigger picture', 'Work with a product catalogue, customers, orders, payments and refunds in one flow. Sales carry pricing, tax, discount and promotion details, and M-Pesa payments are confirmed on the order. Build a repeatable checkout and review order history any time.', ['Catalogue, orders, payments and refunds', 'M-Pesa STK push with automatic reconciliation', 'Promotions, tax and discount records', 'Works through patchy connectivity'], 'Retail counters, in-person sales teams and businesses that need a structured transaction record.'],
    ['inventory', 'box', 'Inventory Management', 'Live', 'Know what is moving and what needs attention', 'Organise product information and monitor stock activity. Ledger-based movements show every change over time, and low-stock views make replenishment easier to plan. Decide which products are stock-tracked and how staff record adjustments.', ['Stock ledger with a history of changes', 'Low-stock views', 'Stock-tracked and non-tracked products', 'Ties directly into POS sales'], 'Businesses managing physical goods, replenishment routines or stock-sensitive sales.'],
    ['crm', 'users', 'Customer Relationship Management', 'Rolling out', 'Turn customer details into a consistent relationship', 'Build on customer records with conversations, opportunities, follow-ups and service needs. Instead of relying on one person’s memory, teams share routines for keeping relationships active and handing them over.', ['Shared customer records', 'Follow-ups and next steps', 'Context for every handover'], 'Sales teams, account owners and service teams coordinating ongoing relationships.'],
]; @endphp

<div class="mx-auto max-w-6xl space-y-8 px-4 py-16 sm:px-6">
    @foreach ($apps as [$id, $icon, $name, $status, $headline, $body, $points, $for])
        @php
            $img = ['pos' => ['mobile-payment', 'A phone confirming a payment'], 'inventory' => ['stock-room', 'A worker checking stock on shelves'], 'crm' => ['support-agent', 'A support agent with a headset helping a customer']][$id];
            $art = ['pos' => 'buy-cart-market', 'inventory' => 'chart-diagram-pie', 'crm' => 'clock-event-planner'][$id];
        @endphp
        <article id="{{ $id }}" data-reveal class="grid gap-6 rounded-3xl bg-surface p-5 shadow-soft ring-1 ring-steel-soft sm:p-10 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <div class="flex items-center gap-3">
                    <span class="grid size-14 place-items-center rounded-2xl bg-primary-soft"><x-ui.art :name="$art" class="size-10" :size="40" /></span>
                    <x-badge :tone="$status === 'Live' ? 'success' : 'primary'">{{ $status }}</x-badge>
                </div>
                <h2 class="mt-5 text-2xl font-black sm:text-3xl">{{ $name }}</h2>
                <h3 class="mt-1 text-lg font-semibold text-primary-600">{{ $headline }}</h3>
                <p class="mt-4 leading-relaxed text-ink-soft">{{ $body }}</p>
                <p class="mt-4 text-base"><span class="font-semibold">Useful for:</span> <span class="text-ink-soft">{{ $for }}</span></p>
            </div>
            <div class="space-y-4 lg:col-span-2"><x-ui.img :src="$img[0]" :alt="$img[1]" class="aspect-[4/3] rounded-2xl" />
            <ul class="space-y-3 rounded-2xl bg-surface-alt p-5 shadow-inset">
                @foreach ($points as $p)
                    <li class="flex items-start gap-3 text-base"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-primary text-white"><x-ui.icon name="check" class="size-3" /></span>{{ $p }}</li>
                @endforeach
            </ul></div>
        </article>
    @endforeach

    <div class="rounded-3xl bg-primary-soft p-8 text-center">
        <x-ui.art name="chart-growth-invest" class="mx-auto size-16" :size="64" />
        <h2 class="mt-2 text-2xl font-black">More apps, introduced with purpose</h2>
        <p class="mx-auto mt-2 max-w-2xl text-ink-soft">Accounting, purchasing, reporting and workforce management may arrive through specific configurations or future releases. Confirm current availability before planning a rollout.</p>
        <x-button class="mt-6" x-on:click="$dispatch('open-dialog', 'demo')">Ask about your workflow</x-button>
    </div>
</div>
</x-layouts.app>

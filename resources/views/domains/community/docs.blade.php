<x-layouts.app title="User guide and documentation" description="Grafame ERP user guide: workspace, products, customers, sales, stock, users and subscriptions.">
@php $sections = [
    ['workspace', '1. Understand your workspace', ['Each organisation works in its own workspace. Platform administrators manage subscriptions and module access, and your role decides which areas and actions you see.', 'If an app or action is missing, confirm you are in the right organisation and account, then ask your workspace owner whether the app is included and your role allows it. Never share login credentials to fix access.']],
    ['onboarding', '2. Prepare for onboarding', ['Agree who owns setup and who uses each workflow. Gather product information, customer details and opening stock, and decide which products need inventory tracking and how corrections are recorded.', 'Start with a manageable set of records. Check names, prices, tax settings and active status. Give staff only the access they need, plus a short walkthrough.']],
    ['products', '3. Work with products', ['Keep product names recognisable, prices current (in KES), and tax and stock-tracking settings aligned with your process. Review records regularly to remove obsolete or duplicate items.', 'When importing, prepare a clean file, follow the fields shown by the import tool, and check a small sample before relying on the full catalogue.']],
    ['customers', '4. Manage customers', ['Use consistent naming, avoid duplicates, and store only what supports the relationship. Follow your organisation’s privacy and retention rules, including Kenya’s Data Protection Act requirements.', 'Related sales history helps staff understand past interactions before assisting a returning customer.']],
    ['sales', '5. Record a sale', ['Select products and quantities, review pricing and promotions, and confirm order details. Attach a customer when appropriate, then record payment (cash, card or M-Pesa) using the methods enabled for your workspace.', 'If a transaction looks wrong, follow the refund or correction process instead of creating a compensating sale without review.']],
    ['stock', '6. Review stock activity', ['Stock levels are only meaningful when recorded activity is consistent. Review stock-tracked products, examine changes and identify low items. Follow your approval and reason-code practices for adjustments.', 'To investigate a difference, check recent sales and movements, then confirm with a physical count. A low-stock flag is a prompt to review, not a purchase order.']],
    ['users', '7. Manage users and access', ['Owners or authorised managers invite users and assign roles that match job responsibilities. Review access when roles change or people leave.']],
    ['billing', '8. Understand subscriptions and apps', ['Your available apps may be tied to your subscription and configuration. Before changing a plan or enabling a module, confirm the effect on access, billing and existing workflows.']],
]; @endphp

<div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-4">
    <aside class="lg:sticky lg:top-24 lg:self-start">
        <h2 class="mb-3 font-ui text-sm font-semibold">On this page</h2>
        <x-scroll-area label="Documentation sections" class="max-h-72 bg-surface p-2 shadow-soft ring-1 ring-steel-soft">
            @foreach ($sections as [$id, $title])
                <a href="#{{ $id }}" class="flex min-h-12 items-center rounded-xl px-3 text-sm transition hover:bg-primary-soft">{{ $title }}</a>
            @endforeach
            <a href="#troubleshooting" class="flex min-h-12 items-center rounded-xl px-3 text-sm transition hover:bg-primary-soft">Troubleshooting</a>
        </x-scroll-area>
    </aside>

    <article class="lg:col-span-3">
        <h1 class="text-4xl font-black">Grafame ERP user guide</h1>
        <p class="mt-3 text-lg text-ink-soft">For owners, managers and staff. Screens and permissions differ by plan, configuration and role, so treat the navigation in your account as the source of truth for enabled apps.</p>
        @foreach ($sections as [$id, $title, $paras])
            <section id="{{ $id }}" class="mt-12">
                <h2 class="text-2xl font-black">{{ $title }}</h2>
                @foreach ($paras as $p)<p class="mt-3 leading-relaxed text-ink-soft">{{ $p }}</p>@endforeach
            </section>
        @endforeach

        <section id="troubleshooting" class="mt-12 rounded-3xl bg-primary-soft p-7">
            <h2 class="text-2xl font-black">Troubleshooting checklist</h2>
            <ul class="mt-4 space-y-3">
                @foreach (['Confirm you are in the right organisation and account.', 'Check that your role allows the action.', 'Verify required product, customer or order fields.', 'Refresh the record and check whether the action already completed.', 'Note the app, time, steps taken and any message shown.', 'Contact support through your account’s route, leaving out passwords and sensitive customer data.'] as $t)
                    <li class="flex items-start gap-3"><x-ui.icon name="check" class="mt-0.5 shrink-0 text-primary" />{{ $t }}</li>
                @endforeach
            </ul>
            <x-button class="mt-6" :href="route('community')" wire:navigate>Get help</x-button>
        </section>
    </article>
</div>
</x-layouts.app>

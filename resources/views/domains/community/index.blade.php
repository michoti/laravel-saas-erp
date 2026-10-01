@php
$faqs = [
 ['What is Grafame ERP?', 'A SaaS platform for organising business operations through a workspace and a set of apps, including subscription and module management and a POS workflow for products, customers, orders, payments, refunds, promotions and stock activity.'],
 ['Is it suitable for my business?', 'It may fit if you want a more organised way to manage sales and related records. The best fit depends on your workflows, team and integrations.'],
 ['Can I use only the apps I need?', 'Yes, the platform is modular. Which apps you can use depends on your plan and configuration.'],
 ['Can I add CRM or accounting later?', 'Additional apps may become available by plan, configuration or release stage. Confirm current availability with our team.'],
 ['How do I get started?', 'Identify the process you want to improve and who will use it. Your onboarding contact confirms the plan and prepares your workspace.'],
];
$faqSchema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)];
@endphp
<x-layouts.app :schema="$faqSchema" title="Help Center, FAQs and support" description="Guides, support and answers for people using Grafame ERP.">
<section class="bg-surface">
    <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:py-16">
        <div>
            <h1 class="text-[2rem] font-black leading-tight sm:text-5xl">Help for the people using Grafame ERP</h1>
            <p class="mt-3 text-base text-ink-soft sm:text-lg">Guides, answers and a human on the other end. Search first, or ask us.</p>
            <div class="mt-6"><livewire:help-search /></div>
        </div>
        <x-ui.img src="support-agent" alt="A support agent with a headset answering customer questions" class="hidden aspect-[4/3] rounded-3xl shadow-soft lg:block" />
    </div>
</section>

<section class="mx-auto grid max-w-6xl gap-5 px-4 py-16 sm:px-6 md:grid-cols-3">
    <div class="rounded-3xl bg-surface p-5 shadow-soft ring-1 ring-steel-soft sm:p-7">
        <x-ui.art name="chart-growth-invest" class="size-14" :size="56" /><h2 class="mt-4 text-lg font-bold">Documentation</h2>
        <p class="mt-2 text-base text-ink-soft">Task-based guides for products, customers, sales, stock, users and subscriptions.</p>
        <x-button variant="secondary" class="mt-5" :href="route('docs')" wire:navigate>Open the guide</x-button>
    </div>
    <div class="rounded-3xl bg-surface p-5 shadow-soft ring-1 ring-steel-soft sm:p-7">
        <x-ui.art name="communication-letter-memo" class="size-14" :size="56" /><h2 class="mt-4 text-lg font-bold">Contact support</h2>
        <p class="mt-2 text-base text-ink-soft">Tell us what you expected, what happened, and whether it affects one user or everyone. Skip passwords and card details.</p>
        <x-button variant="secondary" class="mt-5" :href="'mailto:'.config('marketing.support_email')">Email support</x-button>
    </div>
    <div class="rounded-3xl bg-surface p-5 shadow-soft ring-1 ring-steel-soft sm:p-7">
        <x-ui.art name="black-friday-cheap-discount" class="size-14" :size="56" /><h2 class="mt-4 text-lg font-bold">Share feedback</h2>
        <p class="mt-2 text-base text-ink-soft">Missing something? Tell us which workflow you want to improve and we will factor it into the roadmap.</p>
        <x-button variant="secondary" class="mt-5" x-on:click="$dispatch('open-dialog', 'demo')">Send feedback</x-button>
    </div>
</section>

<section class="mx-auto max-w-3xl px-4 sm:px-6">
    <h2 class="mb-6 text-3xl font-black">Frequently asked questions</h2>
    <x-accordion>
        <x-accordion.item id="f1" title="What is Grafame ERP?">A SaaS platform for organising business operations through a workspace and a set of apps. Today that includes subscription and module management and a POS workflow for products, customers, orders, payments, refunds, promotions and stock activity.</x-accordion.item>
        <x-accordion.item id="f2" title="Is it suitable for my business?">It may fit if you want a more organised way to manage sales and related records. The best fit depends on your workflows, team and integrations, so talk to us before committing to a rollout.</x-accordion.item>
        <x-accordion.item id="f3" title="Can I use only the apps I need?">Yes, the platform is modular. Which apps you can use depends on your plan and configuration. Confirm the exact list and terms before purchase.</x-accordion.item>
        <x-accordion.item id="f4" title="Can I add CRM or accounting later?">Additional apps may become available by plan, configuration or release stage. Do not assume a roadmap item is included in your subscription.</x-accordion.item>
        <x-accordion.item id="f5" title="How do I get started?">Identify the process you want to improve and who will use it. Your onboarding contact confirms the plan, prepares your workspace and helps you plan records and training for a focused launch.</x-accordion.item>
        <x-accordion.item id="f6" title="Where is the documentation?">In the <a href="{{ route('docs') }}" wire:navigate class="font-semibold text-primary-600 underline">Documentation</a>. If a page does not match your access level, contact support with the app name and the step that differs.</x-accordion.item>
    </x-accordion>
</section>
</x-layouts.app>

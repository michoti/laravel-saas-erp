<x-layouts.app title="About Grafame ERP" description="Why we are building Grafame ERP: practical, modular business software for growing Kenyan companies.">
<section class="bg-surface">
    <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:py-16">
        <div>
            <h1 class="text-[2rem] font-black leading-tight sm:text-5xl">Practical software for connected businesses</h1>
            <p class="mt-4 text-base leading-relaxed text-ink-soft sm:text-lg">Grafame ERP brings everyday operations into one coherent system, without a maze of disconnected apps or an all-at-once transformation. Start with what matters most, then expand.</p>
        </div>
        <x-ui.img src="team-planning" alt="A team planning together around a table with a whiteboard of notes" class="aspect-[4/3] rounded-3xl shadow-soft" :eager="true" />
    </div>
</section>

<section class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2">
    <div>
        <h2 class="text-3xl font-black">Why we are building it</h2>
        <p class="mt-4 leading-relaxed text-ink-soft">Growing businesses reach a point where informal systems start to slow them down. A notebook, an Excel sheet or a WhatsApp group was the right first step, but over time important details scatter across files, chats and individual routines. Teams then spend energy checking whether information is current instead of acting on it.</p>
        <p class="mt-4 leading-relaxed text-ink-soft">Grafame ERP makes core activity easier to organise and review: a separate workspace for every business, subscription and module management, and a POS area covering products, customers, orders, payments, refunds, promotions and stock activity.</p>
    </div>
    <x-scroll-area label="What guides the product" class="max-h-[26rem] bg-surface p-3 shadow-soft ring-1 ring-steel-soft">
        @foreach ([
            ['sparkle', 'Clarity before complexity', 'Software should make the next step easier to see. Work is organised around understandable records and processes.'],
            ['layers', 'Progress in manageable stages', 'A modular platform lets teams start with one workflow, establish it, and widen the system when ready.'],
            ['users', 'Shared work, clear responsibility', 'Organisation-specific workspaces with access and responsibilities managed in context.'],
            ['receipt', 'Reliable operational records', 'Transaction and stock records connect to the activity they describe, so it is easy to review what happened.'],
        ] as [$i, $t, $b])
            <div class="flex gap-4 rounded-2xl p-4 transition hover:bg-primary-soft">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary-soft text-primary"><x-ui.icon :name="$i" /></span>
                <div><h3 class="font-bold">{{ $t }}</h3><p class="mt-1 text-base leading-relaxed text-ink-soft">{{ $b }}</p></div>
            </div>
        @endforeach
    </x-scroll-area>
</section>

<section class="mx-auto max-w-6xl px-4 sm:px-6">
    <div class="grid gap-5 md:grid-cols-2">
        <div class="rounded-3xl bg-surface p-6 shadow-soft sm:p-8 ring-1 ring-steel-soft"><x-ui.art name="briefcase-business-case" class="size-14" :size="56" /><h2 class="mt-2 text-xl font-black">Who we serve</h2><p class="mt-3 text-ink-soft">Retailers, wholesalers, pharmacies, hardware shops and service businesses that want to coordinate sales and operational information, from a single Nairobi shop to branches across counties.</p></div>
        <div class="rounded-3xl bg-surface p-6 shadow-soft sm:p-8 ring-1 ring-steel-soft"><x-ui.art name="check-money-order" class="size-14" :size="56" /><h2 class="mt-2 text-xl font-black">Our commitment</h2><p class="mt-3 text-ink-soft">Clear product communication, considered improvements, and support that helps you pick a practical next step. Exact features depend on plan, configuration and release stage.</p></div>
    </div>
    <div class="mt-8 rounded-3xl bg-primary-soft p-10 text-center">
        <h2 class="text-2xl font-black">Build the next stage with us</h2>
        <p class="mx-auto mt-2 max-w-xl text-ink-soft">Replacing scattered tools or setting up a process for the first time? Start with the challenge you want to solve.</p>
        <x-button class="mt-6" x-on:click="$dispatch('open-dialog', 'demo')">Meet the platform</x-button>
    </div>
</section>
</x-layouts.app>

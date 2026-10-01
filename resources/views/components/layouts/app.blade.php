@props([
    'title' => null,
    'description' => 'Grafame ERP is a modular business platform for Kenyan businesses: point of sale with M-Pesa, inventory and customer records in one workspace.',
    'image' => null,
    'noindex' => false,
    'schema' => null,
])
@php
$brand = config('app.name', 'Grafame ERP');
$fullTitle = $title ? "$title | $brand" : "$brand: POS, inventory and CRM for Kenyan businesses";
$canonical = url()->current();
$ogImage = $image ?? asset('images/og-default.png');
$site = [
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'Organization', '@id' => url('/').'#org', 'name' => $brand, 'legalName' => 'Grafame Tech', 'sameAs' => [config('marketing.facebook_url')], 'url' => url('/'),
         'logo' => asset('images/logo.png'), 'email' => config('marketing.support_email'),
         'telephone' => config('marketing.support_phone'),
         'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Nairobi', 'addressCountry' => 'KE']],
        ['@type' => 'WebSite', '@id' => url('/').'#website', 'url' => url('/'), 'name' => $brand,
         'inLanguage' => 'en-KE', 'publisher' => ['@id' => url('/').'#org']],
        ['@type' => 'SoftwareApplication', 'name' => $brand, 'applicationCategory' => 'BusinessApplication',
         'operatingSystem' => 'Web', 'description' => 'Modular ERP with point of sale, inventory and CRM for Kenyan businesses.',
         'offers' => ['@type' => 'Offer', 'priceCurrency' => 'KES', 'url' => route('pricing')]],
    ],
];
$json = fn ($d) => json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="{{ $noindex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large' }}">
    <meta name="theme-color" content="#ED0909">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/icon-256.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icon-256.png') }}">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:locale" content="en_KE">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- Structured data --}}
    <script type="application/ld+json">{!! $json($site) !!}</script>
    @if ($schema)<script type="application/ld+json">{!! $json($schema) !!}</script>@endif
    <script>document.documentElement.classList.add('js');setTimeout(function(){if(!window.__motionReady)document.documentElement.classList.remove('js')},3000)</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&family=Noto+Sans:wght@500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&family=Noto+Sans:wght@500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet"></noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
$nav = [['Pricing', 'pricing'], ['About', 'about'], ['Community', 'community'], ['Docs', 'docs']];
$apps = [
    ['Point of Sale', 'Checkout, M-Pesa and refunds', 'buy-cart-market', 'Live'],
    ['Inventory', 'Stock ledger and low-stock alerts', 'chart-diagram-pie', 'Live'],
    ['CRM', 'Customer follow-ups and history', 'clock-event-planner', 'Rolling out'],
];
$tel = preg_replace('/\s+/', '', config('marketing.support_phone'));
@endphp
<body class="min-h-dvh bg-surface-alt">
<a href="#main" class="sr-only z-[60] rounded-full bg-ink px-4 py-3 text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

{{-- Header: logo left, secondary action right. Primary actions live in the thumb-zone bar below on phones. --}}
<header class="sticky top-0 z-40 border-b border-steel-soft/70 bg-surface/90 pt-[env(safe-area-inset-top)] backdrop-blur-lg">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-4 sm:px-6">
        <a href="{{ route('home') }}" wire:navigate.hover class="flex min-h-12 items-center gap-2.5" aria-label="Grafame ERP, home">
            <img src="{{ asset('images/logo-mark.png') }}" alt="Grafame Tech logo" width="48" height="38" class="h-9 w-auto" fetchpriority="high">
            <span class="font-heading text-xl font-black tracking-tight">Grafame<span class="text-primary"> ERP</span></span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary">
            <x-dropdown-menu label="Apps">
                @foreach ($apps as [$name, $blurb, $art, $status])
                    <a role="menuitem" href="{{ route('apps') }}" wire:navigate class="flex items-center gap-3 rounded-xl p-3 transition hover:bg-primary-soft">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary-soft"><x-ui.art :name="$art" class="size-8" :size="32" /></span>
                        <span><span class="block font-ui text-sm font-semibold">{{ $name }}</span><span class="block text-sm text-ink-soft">{{ $blurb }}</span></span>
                    </a>
                @endforeach
            </x-dropdown-menu>
            @foreach ($nav as [$label, $route])
                <a href="{{ route($route) }}" wire:navigate.hover @if (request()->routeIs($route)) aria-current="page" @endif
                   class="inline-flex min-h-12 items-center rounded-full px-4 font-ui text-sm font-semibold transition hover:bg-primary-soft {{ request()->routeIs($route) ? 'text-primary-600' : 'text-ink' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-1 sm:gap-2">
            <x-button variant="ghost" :href="config('marketing.login_url')" class="px-4">Sign in</x-button>
            <x-button x-on:click="$dispatch('open-dialog', 'demo')" class="hidden lg:inline-flex">Book a demo</x-button>
        </div>
    </div>
</header>

<main id="main" class="overflow-x-clip">{{ $slot }}</main>

{{-- Footer --}}
<footer class="mt-20 bg-ink pb-28 text-white lg:pb-0">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-4">
        <div class="md:col-span-2">
            <span class="inline-block rounded-2xl bg-white p-3"><img src="{{ asset('images/logo.png') }}" alt="Grafame Tech" width="165" height="94" loading="lazy" class="h-16 w-auto"></span>
            <p class="mt-4 max-w-sm text-base leading-relaxed text-steel">Practical business software for Kenyan teams. Start with the workflow you need today and add apps as you grow.</p>
            <a href="{{ config('marketing.facebook_url') }}" target="_blank" rel="noopener" data-squash class="mt-4 inline-flex min-h-12 items-center gap-3 rounded-full bg-white px-4 font-ui text-sm font-semibold text-ink"><x-ui.art name="facebook" class="size-6" :size="24" />Follow us on Facebook</a>
        </div>
        <div>
            <h2 class="font-ui text-base font-semibold text-white">Explore</h2>
            <ul class="mt-3 space-y-1 text-base text-steel">
                @foreach ([['Apps', 'apps'], ['Pricing', 'pricing'], ['About', 'about'], ['Community', 'community'], ['Documentation', 'docs']] as [$l, $r])
                    <li><a href="{{ route($r) }}" wire:navigate class="inline-flex min-h-12 items-center transition hover:text-white">{{ $l }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2 class="font-ui text-base font-semibold text-white">Talk to us</h2>
            <ul class="mt-3 space-y-1 text-base text-steel">
                <li><a href="mailto:{{ config('marketing.support_email') }}" class="inline-flex min-h-12 items-center gap-2 break-all transition hover:text-white"><x-ui.icon name="mail" class="size-5 shrink-0" />{{ config('marketing.support_email') }}</a></li>
                <li><a href="tel:{{ $tel }}" class="inline-flex min-h-12 items-center gap-2 transition hover:text-white"><x-ui.icon name="phone" class="size-5 shrink-0" />{{ config('marketing.support_phone') }}</a></li>
                <li class="inline-flex min-h-12 items-center gap-2"><x-ui.icon name="map-pin" class="size-5 shrink-0" />{{ config('marketing.address') }}</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10 px-4 py-6 text-center text-sm text-steel">&copy; {{ date('Y') }} Grafame Tech. Module availability depends on your plan and rollout.</div>
</footer>

{{-- Thumb-zone action bar (phones/tablets): the primary action sits where the thumb rests --}}
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-steel-soft bg-surface/95 px-3 pt-3 pb-[max(.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgb(15_23_42/.2)] backdrop-blur-lg lg:hidden">
    <div class="mx-auto flex max-w-lg items-center gap-2">
        <button type="button" data-squash x-on:click="$dispatch('open-dialog', 'menu')" aria-label="Open menu" class="grid size-12 shrink-0 place-items-center rounded-full bg-surface-alt text-ink ring-1 ring-steel-soft"><x-ui.icon name="menu" class="size-6" /></button>
        <a href="tel:{{ $tel }}" data-squash aria-label="Call us" class="grid size-12 shrink-0 place-items-center rounded-full bg-surface-alt text-ink ring-1 ring-steel-soft"><x-ui.icon name="phone" class="size-6" /></a>
        <x-button class="flex-1" x-on:click="$dispatch('open-dialog', 'demo')">Book a demo</x-button>
    </div>
</div>

{{-- Menu sheet (opened from the bar above) --}}
<x-dialog name="menu" title="Menu">
    <nav class="-mx-2 flex flex-col" aria-label="Mobile" x-on:click="$dispatch('close-dialog')">
        @foreach ([['Apps', 'apps', 'buy-cart-market'], ['Pricing', 'pricing', 'bag-cash-currency'], ['About', 'about', 'briefcase-business-case'], ['Community', 'community', 'communication-letter-memo'], ['Docs', 'docs', 'chart-growth-invest']] as [$l, $r, $art])
            <a href="{{ route($r) }}" wire:navigate class="flex min-h-14 items-center gap-4 rounded-2xl px-3 text-lg font-semibold transition active:bg-primary-soft">
                <span class="grid size-11 place-items-center rounded-xl bg-primary-soft"><x-ui.art :name="$art" class="size-8" :size="32" /></span>{{ $l }}
            </a>
        @endforeach
        <a href="{{ config('marketing.login_url') }}" class="mt-3 flex min-h-14 items-center justify-center rounded-full bg-surface-alt font-ui text-base font-semibold ring-1 ring-steel-soft">Sign in</a>
    </nav>
</x-dialog>

<x-dialog name="demo" title="Book a demo"><livewire:demo-request /></x-dialog>

{{-- Toast: flash messages and Livewire 'notify' events (sits above the action bar on phones) --}}
<div x-data="toast(@js(session('status')))" x-on:notify.window="show($event.detail.message)" class="pointer-events-none fixed inset-x-0 bottom-24 z-[70] flex justify-center px-4 lg:bottom-6">
    <div x-ref="box" x-show="message" x-cloak role="status" aria-live="polite" class="pointer-events-auto flex w-full max-w-md items-center gap-3 rounded-2xl bg-ink py-3 pr-2 pl-4 text-base text-white shadow-soft-lg">
        <span x-ref="icon" class="grid size-8 shrink-0 place-items-center rounded-full bg-emerald-500"><x-ui.icon name="check" class="size-5" /></span>
        <span class="flex-1" x-text="message"></span>
        <button type="button" x-on:click="message = null" aria-label="Dismiss" class="grid size-12 shrink-0 place-items-center rounded-full text-steel hover:text-white"><x-ui.icon name="x" class="size-5" /></button>
    </div>
</div>

@livewireScriptConfig
</body>
</html>

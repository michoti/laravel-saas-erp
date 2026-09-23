<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} — POS Architecture</title>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            :root {
                color-scheme: light dark;
            }
            body {
                font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
                background: #FDFDFC;
                color: #1b1b18;
                margin: 0;
                padding: 0;
            }
            @media (prefers-color-scheme: dark) {
                body { background: #0a0a0a; color: #EDEDEC; }
                .card { background: #161615 !important; box-shadow: inset 0 0 0 1px #fffaed2d !important; }
                .pill { background: #3E3E3A !important; color: #EDEDEC !important; }
                .muted { color: #A1A09A !important; }
                code, .mono { background: #1D0002 !important; color: #FF4433 !important; }
                a.accent { color: #FF4433 !important; }
                h1, h2 { color: #EDEDEC !important; }
                .divider { border-color: #3E3E3A !important; }
            }
            .wrap {
                max-width: 960px;
                margin: 0 auto;
                padding: 3rem 1.5rem 5rem;
            }
            .card {
                background: #fff;
                box-shadow: inset 0 0 0 1px rgba(26,26,0,0.16);
                border-radius: 0.5rem;
                padding: 1.75rem 2rem;
                margin-bottom: 1.5rem;
            }
            h1 {
                font-size: 1.5rem;
                font-weight: 600;
                margin: 0 0 0.5rem;
                color: #1b1b18;
            }
            h2 {
                font-size: 1.05rem;
                font-weight: 600;
                margin: 0 0 0.75rem;
                color: #1b1b18;
            }
            p {
                line-height: 1.65;
                font-size: 0.95rem;
                margin: 0 0 0.75rem;
            }
            .muted { color: #706f6c; }
            .lede {
                font-size: 1rem;
                margin-bottom: 2rem;
            }
            .stack {
                font-size: 0.85rem;
                display: flex;
                flex-wrap: wrap;
                gap: 0.4rem;
                margin-bottom: 2rem;
            }
            .pill {
                background: #f3f3f1;
                border-radius: 999px;
                padding: 0.25rem 0.75rem;
                font-size: 0.8rem;
                color: #1b1b18;
            }
            .num {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.6rem;
                height: 1.6rem;
                border-radius: 999px;
                background: #F53003;
                color: #fff;
                font-size: 0.8rem;
                font-weight: 600;
                margin-right: 0.6rem;
                flex-shrink: 0;
            }
            .section-title {
                display: flex;
                align-items: center;
            }
            code, .mono {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
                background: #fff2f2;
                color: #F53003;
                padding: 0.1rem 0.35rem;
                border-radius: 0.25rem;
                font-size: 0.85em;
            }
            a.accent {
                color: #F53003;
                font-weight: 500;
                text-decoration: underline;
                text-underline-offset: 3px;
            }
            .divider {
                border: none;
                border-top: 1px solid #e3e3e0;
                margin: 2.5rem 0 2rem;
            }
            footer {
                text-align: center;
                font-size: 0.8rem;
                color: #706f6c;
                margin-top: 2rem;
            }
        </style>
    </head>
    <body>
        <div class="wrap">
            <h1>Odoo‑Replica SaaS — POS Module Architecture</h1>
            <p class="lede muted">
                A brief walkthrough of the offline‑first, multi‑tenant Point of Sale architecture
                ({{ config('app.name', 'Laravel') }} v{{ app()->version() }}).
            </p>

            <div class="stack">
                <span class="pill">PHP 8.4</span>
                <span class="pill">Laravel 13.17</span>
                <span class="pill">Filament v5</span>
                <span class="pill">PostgreSQL 18</span>
                <span class="pill">Redis / Horizon</span>
                <span class="pill">Reverb</span>
                <span class="pill">stancl/tenancy 3.10</span>
                <span class="pill">nwidart/laravel-modules 13</span>
            </div>

            <div class="card">
                <h2 class="section-title"><span class="num">•</span>Overall shape</h2>
                <p>
                    A multi‑tenant SaaS that replicates core Odoo‑style POS functionality, built as
                    a self‑contained module (<code>Modules/Pos</code>) via
                    <code>nwidart/laravel-modules</code> — routes, models, migrations, jobs, and
                    tests live under <code>Modules/Pos/</code> and are registered by their own
                    <code>PosServiceProvider</code>, rather than mixed into the shared
                    <code>app/</code> tree.
                </p>
            </div>

            <div class="card">
                <h2 class="section-title"><span class="num">1</span>Multi‑tenancy</h2>
                <p>
                    Each tenant gets its own database via <code>stancl/tenancy</code>. A request
                    first resolves the tenant from a central database (by subdomain or bearer
                    token), then the app swaps its DB / cache / queue / session connections to
                    that tenant's isolated database.
                </p>
                <p class="muted">
                    This means there's no shared <code>tenant_id</code> column to filter on —
                    tenant data (products, orders, payments, users, audit logs) is physically
                    separated, so there's no risk of a forgotten <code>WHERE</code> clause leaking
                    data across tenants. Only <code>tenants</code>, Cashier subscriptions, and the
                    module‑activation registry live centrally.
                </p>
            </div>

            <div class="card">
                <h2 class="section-title"><span class="num">2</span>Offline‑first sync</h2>
                <p>
                    POS devices work offline, queuing orders, order items, payments, and stock
                    movements locally with client‑generated UUIDv7 IDs. When connectivity returns,
                    the device <code>POST</code>s a batch to <code>/api/pos/sync/batch</code>.
                </p>
                <p class="muted">
                    The server queues the batch idempotently (keyed by <code>batch_id</code>) and
                    hands it to a background job on a dedicated Horizon queue. That job, inside a
                    single DB transaction, upserts orders, assigns the <em>real</em> sequential
                    invoice number server‑side from a Postgres sequence (never trusting the
                    client), appends immutable stock‑ledger rows, and records payments — then
                    broadcasts the result over WebSockets (Reverb) so every other device for that
                    tenant updates live.
                </p>
            </div>

            <div class="card">
                <h2 class="section-title"><span class="num">3</span>Reference data sync</h2>
                <p>
                    Products, price lists, and customers use a simpler Last‑Write‑Wins scheme:
                    every update carries a client version number. The server rejects stale writes
                    with a <code>409</code> and returns the current server state, or accepts newer
                    writes and applies them — pushing the change out to connected devices over
                    Reverb as well, so a back‑office price change lands on the till instantly.
                </p>
            </div>

            <div class="card">
                <h2 class="section-title"><span class="num">4</span>M‑Pesa payments</h2>
                <p>
                    STK push is initiated asynchronously via a queued job. A public,
                    IP‑allowlisted webhook callback then resolves the payment through a single
                    shared, idempotency‑guarded resolver.
                </p>
                <p class="muted">
                    Because callbacks can be dropped or never arrive, a per‑minute scheduled
                    reconciliation job also polls M‑Pesa directly for any payment stuck
                    "awaiting confirmation," calling that same resolver — so whichever path
                    resolves first wins cleanly, the other is a no‑op, and nothing hangs forever
                    (worst case it resolves within a configurable give‑up window).
                </p>
            </div>

            <div class="card">
                <h2 class="section-title"><span class="num">5</span>Reporting read‑models</h2>
                <p>
                    Reporting is decoupled from the transactional tables. A scheduled Horizon job
                    periodically rolls up <code>stock_ledger</code>, <code>orders</code>, and
                    <code>payments</code> into read‑only summary tables (daily sales, stock on
                    hand, top products).
                </p>
                <p class="muted">
                    Filament dashboard widgets and Excel/PDF exports read only from these
                    read‑models — never the live write path — so reporting load never competes
                    with POS write throughput.
                </p>
            </div>

            <hr class="divider">

            <p class="muted" style="font-size: 0.85rem;">
                Read more about the underlying framework at
                <a href="https://laravel.com/docs" target="_blank" class="accent">Laravel Documentation</a>
                or watch tutorials at
                <a href="https://laracasts.com" target="_blank" class="accent">Laracasts</a>.
            </p>

            <footer>
                {{ config('app.name', 'Laravel') }} v{{ app()->version() }} · Modules/Pos architecture overview
            </footer>
        </div>
    </body>
</html>
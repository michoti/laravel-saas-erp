<?php

declare(strict_types=1);

namespace App\Filament\Central\Widgets;

use App\Enums\SubscriptionStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Monthly recurring revenue, derived entirely from this platform's own
 * `subscriptions`/`plans` tables (central DB) — no Laravel Cashier or
 * third-party payment processor involved anywhere in this codebase — billing runs on M-Pesa via
 * App\Services\Billing\*. Cached for an hour — billing data doesn't need
 * to be second-fresh on a chart.
 */
final class MrrTrendChart extends ChartWidget
{
    protected ?string $heading = 'MRR trend (last 6 months)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $rows = Cache::tags(['central-dashboard'])->remember('central:mrr-trend-chart', now()->addHour(), function (): array {
            return DB::connection('central')->table('subscriptions')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->selectRaw("to_char(subscriptions.created_at, 'YYYY-MM') AS month, SUM(plans.price) AS mrr")
                ->whereIn('subscriptions.status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
                ->where('subscriptions.created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('mrr', 'month')
                ->all();
        });

        return [
            'datasets' => [[
                'label' => 'MRR (KES)',
                'data' => array_values($rows),
                'backgroundColor' => '#10b981',
                'borderColor' => '#10b981',
            ]],
            'labels' => array_keys($rows),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

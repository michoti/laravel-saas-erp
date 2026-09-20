<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Modules\Pos\Filament\Widgets\SalesTrendChartWidget;
use PHPUnit\Framework\TestCase;

final class SalesTrendChartWidgetTest extends TestCase
{
    public function test_sales_trend_widget_falls_back_to_orders_when_summary_table_is_missing(): void
    {
        $container = new Container();
        Container::setInstance($container);

        $config = new Repository([
            'database.default' => 'testing',
            'database.connections.testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'cache.default' => 'array',
            'cache.stores.array' => [
                'driver' => 'array',
            ],
        ]);

        $container->instance('config', $config);

        $capsule = new Capsule($container);
        $capsule->addConnection($config->get('database.connections.testing'));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $container->instance('db', $capsule->getDatabaseManager());
        $container->instance('db.factory', $capsule->getDatabaseManager());

        $cacheManager = new CacheManager($container);
        $container->instance('cache', $cacheManager);
        $container->instance('cache.store', new ArrayStore());

        Facade::setFacadeApplication($container);

        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('status');
            $table->timestamp('order_date');
            $table->decimal('grand_total', 14, 2)->default(0);
        });

        DB::table('orders')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'status' => 'paid', 'order_date' => '2026-08-20 09:00:00', 'grand_total' => 100.00],
            ['id' => '22222222-2222-4222-8222-222222222222', 'status' => 'paid', 'order_date' => '2026-08-20 15:00:00', 'grand_total' => 50.50],
            ['id' => '33333333-3333-4333-8333-333333333333', 'status' => 'draft', 'order_date' => '2026-08-21 10:00:00', 'grand_total' => 999.99],
        ]);

        $widget = new class extends SalesTrendChartWidget {
            public function evaluate(): array
            {
                return $this->getData();
            }
        };

        $data = $widget->evaluate();

        $this->assertSame(['2026-08-20'], array_keys($data['labels']));
        $this->assertSame([150.5], $data['datasets'][0]['data']);
    }
}

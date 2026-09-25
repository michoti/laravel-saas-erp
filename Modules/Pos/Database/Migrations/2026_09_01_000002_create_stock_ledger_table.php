<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL 18, RANGE-partitioned by month on `occurred_at`. This table
 * is append-only and grows forever by design (see the trigger at the
 * bottom) — for a busy multi-year tenant it's the single largest table in
 * the schema, and monthly partitions keep INSERT and the
 * (product_id, occurred_at) range scans the read-models rely on fast
 * regardless of how many years of history accumulate, since a query
 * scoped to a date range only ever touches the relevant partitions.
 *
 * Declarative partitioning is created via raw SQL because Laravel's
 * Schema builder has no first-class PARTITION BY support. Two
 * consequences worth knowing:
 *   - The primary key must include the partition key (a Postgres
 *     requirement for ANY unique constraint on a partitioned table), so
 *     it's a composite (id, occurred_at) rather than `id` alone. `id`
 *     remains globally unique on its own (UUIDv7), so this has no
 *     practical effect on application code — Eloquent still just does
 *     `WHERE id = ?` everywhere.
 *   - New partitions must be provisioned ahead of time — see
 *     `pos:ensure-stock-ledger-partitions`, scheduled monthly (routes/
 *     console.php) — or an INSERT for a date outside any existing
 *     partition would fail. A DEFAULT partition below is a safety net for
 *     rows that land there anyway (a device with a badly wrong clock,
 *     for instance) rather than a hard failure.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'stock_movement_type') THEN
                    CREATE TYPE stock_movement_type AS ENUM (
                        'sale', 'return', 'purchase', 'adjustment', 'transfer_in', 'transfer_out'
                    );
                END IF;
            END
            $$
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE stock_ledger (
                id uuid NOT NULL,
                product_id uuid NOT NULL,
                warehouse_id uuid NULL,
                movement_type stock_movement_type NOT NULL,
                quantity_delta numeric(14, 4) NOT NULL,
                reference_type varchar(255) NULL,
                reference_id uuid NULL,
                device_id uuid NULL,
                note text NULL,
                occurred_at timestamptz NOT NULL,
                client_created_at timestamptz NULL,
                client_updated_at timestamptz NULL,
                synced_at timestamptz NULL,
                version bigint NOT NULL DEFAULT 1,
                created_at timestamptz NOT NULL DEFAULT now(),
                PRIMARY KEY (id, occurred_at),
                FOREIGN KEY (product_id) REFERENCES products (id)
                    ON UPDATE CASCADE ON DELETE RESTRICT
            ) PARTITION BY RANGE (occurred_at)
        SQL);

        DB::statement('CREATE INDEX stock_ledger_product_id_index ON stock_ledger (product_id)');
        DB::statement('CREATE INDEX stock_ledger_warehouse_id_index ON stock_ledger (warehouse_id)');
        DB::statement('CREATE INDEX stock_ledger_product_id_occurred_at_index ON stock_ledger (product_id, occurred_at)');
        DB::statement('CREATE INDEX stock_ledger_reference_type_reference_id_index ON stock_ledger (reference_type, reference_id)');

        // Belt-and-braces: prevent UPDATE/DELETE at the database level so the
        // ledger stays truly append-only even against a bug or a raw SQL
        // slip. Defined once on the PARENT partitioned table — Postgres
        // (11+) automatically applies a parent's BEFORE trigger to every
        // partition, current and future, with no per-partition setup.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_stock_ledger_mutation()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'stock_ledger is append-only: % not permitted', TG_OP;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_stock_ledger_no_update
            BEFORE UPDATE OR DELETE ON stock_ledger
            FOR EACH ROW EXECUTE FUNCTION prevent_stock_ledger_mutation();
        SQL);

        $this->createMonthlyPartitions();

        // Safety net: an INSERT whose occurred_at falls outside every
        // explicit partition lands here instead of failing outright —
        // pos:ensure-stock-ledger-partitions logs a warning if this
        // partition ever receives rows, since that indicates a clock
        // problem on some device rather than normal operation.
        DB::statement('CREATE TABLE stock_ledger_default PARTITION OF stock_ledger DEFAULT');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS stock_ledger CASCADE'); // CASCADE drops every partition with it
        DB::statement('DROP FUNCTION IF EXISTS prevent_stock_ledger_mutation');
        DB::statement('DROP TYPE IF EXISTS stock_movement_type');
    }

    /** Pre-provisions partitions for the 3 months before/after "now", at migration time. */
    private function createMonthlyPartitions(): void
    {
        $start = now()->subMonths(3)->startOfMonth();

        for ($i = 0; $i < 7; $i++) {
            $from = $start->clone()->addMonths($i);
            $to = $from->clone()->addMonth();
            $suffix = $from->format('Y_m');

            DB::statement(<<<SQL
                CREATE TABLE IF NOT EXISTS stock_ledger_{$suffix}
                PARTITION OF stock_ledger
                FOR VALUES FROM ('{$from->toDateString()}') TO ('{$to->toDateString()}')
            SQL);
        }
    }
};

<?php

declare(strict_types=1);

namespace Modules\Pos\Policies;

use App\Models\User;
use Modules\Pos\App\Models\StockLedgerEntry;

final class StockLedgerEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_stock_ledger_entry');
    }

    public function view(User $user, StockLedgerEntry $entry): bool
    {
        return $user->can('view_any_stock_ledger_entry');
    }

    // create/update/delete are intentionally absent — the ledger is
    // append-only at the database level (see the BEFORE UPDATE OR DELETE
    // trigger in its migration); no policy grants would change that, but
    // omitting them keeps Filament from ever rendering edit/delete actions.
}

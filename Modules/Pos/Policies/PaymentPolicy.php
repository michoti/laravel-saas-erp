<?php

declare(strict_types=1);

namespace Modules\Pos\Policies;

use App\Models\User;
use Modules\Pos\App\Models\Payment;

final class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payment');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('view_payment');
    }

    public function create(User $user): bool
    {
        return false; // payments are only ever created by ProcessSyncBatchJob / M-Pesa callback
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}

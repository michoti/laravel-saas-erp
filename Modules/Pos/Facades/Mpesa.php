<?php

declare(strict_types=1);

namespace Modules\Pos\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Pos\Services\Mpesa\PosMpesaGateway;

/**
 * @method static array stkPush(string $phone, int $amount, string $accountReference, string $transactionDesc, string $callbackUrl)
 * @method static array stkPushQuery(string $checkoutRequestId)
 *
 * @see PosMpesaGateway
 */
final class Mpesa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PosMpesaGateway::class;
    }
}

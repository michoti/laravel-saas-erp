<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Pos\DataTransferObjects\CartLineData;
use Modules\Pos\DataTransferObjects\PaymentLineData;
use Modules\Pos\Jobs\GenerateReceiptPdfJob;
use Modules\Pos\App\Models\Customer;
use Modules\Pos\App\Models\Order;
use Modules\Pos\App\Models\Product;
use Modules\Pos\Services\PosCheckoutService;

/**
 * The actual till screen. Deliberately built as a single Livewire page with
 * plain PHP array state (not a multi-step Filament Form wizard) — every
 * cashier interaction (scan, tap, adjust qty, add a split-payment row) is
 * ONE click/keystroke and re-renders only the cart partial, which is what
 * "minimum clicks, fastest checkout" actually requires in practice.
 */
final class PosTerminal extends Page
{
    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?string $navigationLabel = 'POS Terminal';

    protected static ?int $navigationSort = 0;

    protected string $view = 'pos::filament.pages.pos-terminal';

    // --- Cart state -------------------------------------------------
    public array $cart = [];

    public ?string $barcodeInput = '';

    public ?string $productSearch = '';

    public array $searchResults = [];

    public ?string $customerId = null;

    public ?string $customerSearch = '';

    public array $customerResults = [];

    // --- Split payment state -----------------------------------------
    public array $paymentLines = [
        ['method' => 'cash', 'amount' => null, 'payer_phone' => null],
    ];

    public ?Order $lastCompletedOrder = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('create_order') ?? false;
    }

    /**
     * Fired on every Enter keypress in the barcode field (a USB barcode
     * scanner behaves exactly like a very fast typist followed by Enter —
     * no special driver needed, see the Blade view's @keydown.enter).
     * One scan = one line added, zero additional clicks.
     */
    public function scanBarcode(): void
    {
        $code = trim((string) $this->barcodeInput);
        $this->barcodeInput = '';

        if ($code === '') {
            return;
        }

        $product = Product::query()->where('barcode', $code)->orWhere('sku', $code)->first();

        if ($product === null) {
            Notification::make()->title("No product matches \"{$code}\"")->warning()->send();

            return;
        }

        $this->addProduct($product->id);
    }

    /** Debounced live search backing the product grid / autocomplete — see updatedProductSearch(). */
    public function updatedProductSearch(): void
    {
        if (strlen($this->productSearch ?? '') < 2) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = Product::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->where('name', 'ilike', "%{$this->productSearch}%")
                    ->orWhere('sku', 'ilike', "%{$this->productSearch}%")
                    ->orWhere('barcode', $this->productSearch);
            })
            ->withSum('stockLedgerEntries as stock_on_hand', 'quantity_delta')
            ->limit(12)
            ->get()
            ->toArray();
    }

    public function updatedCustomerSearch(): void
    {
        if (strlen($this->customerSearch ?? '') < 2) {
            $this->customerResults = [];

            return;
        }

        $this->customerResults = Customer::query()
            ->where('name', 'ilike', "%{$this->customerSearch}%")
            ->orWhere('phone', 'like', "%{$this->customerSearch}%")
            ->limit(8)
            ->get(['id', 'name', 'phone', 'store_credit_balance'])
            ->toArray();
    }

    public function selectCustomer(string $customerId): void
    {
        $this->customerId = $customerId;
        $this->customerResults = [];
        $this->customerSearch = '';
    }

    public function addProduct(string $productId): void
    {
        $product = Product::with('promotions')->findOrFail($productId);

        $unitPrice = (float) $product->unit_price;
        if ($promotion = $product->activePromotion()) {
            $unitPrice = $promotion->apply($unitPrice);
        }

        foreach ($this->cart as $index => $line) {
            if ($line['product_id'] === $productId) {
                $this->cart[$index]['quantity']++;

                return;
            }
        }

        $this->cart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'image_path' => $product->image_path,
            'base_price' => (float) $product->unit_price,
            'unit_price' => $unitPrice,
            'tax_rate' => (float) $product->tax_rate,
            'has_promotion' => $unitPrice < (float) $product->unit_price,
            'quantity' => 1,
        ];

        $this->searchResults = [];
        $this->productSearch = '';
    }

    public function incrementQty(int $index): void
    {
        $this->cart[$index]['quantity']++;
    }

    public function decrementQty(int $index): void
    {
        if ($this->cart[$index]['quantity'] > 1) {
            $this->cart[$index]['quantity']--;
        } else {
            $this->removeItem($index);
        }
    }

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function addPaymentRow(): void
    {
        $this->paymentLines[] = ['method' => 'cash', 'amount' => null, 'payer_phone' => null];
    }

    public function removePaymentRow(int $index): void
    {
        unset($this->paymentLines[$index]);
        $this->paymentLines = array_values($this->paymentLines);
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cart)->sum(function (array $line): float {
            $lineSubtotal = $line['unit_price'] * $line['quantity'];

            return round($lineSubtotal + round($lineSubtotal * $line['tax_rate'], 2), 2);
        });
    }

    public function getPaymentTotalProperty(): float
    {
        return collect($this->paymentLines)->sum(fn (array $line): float => (float) ($line['amount'] ?? 0));
    }

    /** One click: validate, persist, clear cart, offer receipt. */
    public function checkout(PosCheckoutService $checkoutService): void
    {
        try {
            $order = $checkoutService->checkout(
                cartLines: collect($this->cart)->map(fn (array $l): CartLineData => CartLineData::fromArray([
                    'product_id' => $l['product_id'],
                    'quantity' => $l['quantity'],
                    'unit_price' => $l['unit_price'],
                ])),
                paymentLines: collect($this->paymentLines)->map(fn (array $l): PaymentLineData => PaymentLineData::fromArray($l)),
                customerId: $this->customerId,
                cashierUserId: Auth::id(),
            );
        } catch (\RuntimeException $e) {
            Notification::make()->title('Checkout failed')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->lastCompletedOrder = $order;
        $this->cart = [];
        $this->paymentLines = [['method' => 'cash', 'amount' => null, 'payer_phone' => null]];
        $this->customerId = null;

        // Sidebar "low stock" badge just changed for up to N products —
        // bust it now instead of waiting out its TTL (see ProductResource
        // navigation badge caching).
        Cache::forget('pos:nav:low-stock-count');

        GenerateReceiptPdfJob::dispatch($order->id)->onQueue('exports');

        Notification::make()
            ->title("Sale complete — {$order->invoice_number}")
            ->success()
            ->send();
    }
}

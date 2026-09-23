<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ============== LEFT: scan / search / product grid ============== --}}
        <div class="space-y-4 lg:col-span-2">

            {{-- Barcode scan input — a USB scanner "types" the code then sends
                 Enter, so this is the entire barcode-scanning integration: no
                 native driver, no browser API, just a focused text input. --}}
            <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <label class="fi-fo-field-wrp-label mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Scan barcode or SKU
                </label>
                <input
                    type="text"
                    autofocus
                    wire:model="barcodeInput"
                    wire:keydown.enter.prevent="scanBarcode"
                    placeholder="Scan or type a barcode, press Enter…"
                    class="fi-input block w-full rounded-lg border-none bg-gray-50 py-3 text-lg shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-white/10"
                />
            </div>

            {{-- Product search: debounced 400ms so typing doesn't fire a
                 network request on every keystroke (per performance guidance) --}}
            <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Search products (name, SKU, category)
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="productSearch"
                    placeholder="e.g. \"soap\" or \"BEV-\"…"
                    class="fi-input block w-full rounded-lg border-none bg-gray-50 py-2.5 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-white/10"
                />

                {{-- Skeleton loading state while the debounced search resolves --}}
                <div wire:loading wire:target="productSearch" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="animate-pulse space-y-2 rounded-lg bg-gray-100 p-3 dark:bg-gray-800">
                            <div class="h-16 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-3 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-3 w-1/2 rounded bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    @endfor
                </div>

                {{-- Tap-to-add product grid, with images — one click adds to cart --}}
                <div wire:loading.remove wire:target="productSearch" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @forelse ($searchResults as $product)
                        <button
                            type="button"
                            wire:click="addProduct('{{ $product['id'] }}')"
                            class="group relative flex flex-col overflow-hidden rounded-lg bg-gray-50 text-left ring-1 ring-gray-950/10 transition hover:ring-primary-500 dark:bg-gray-800 dark:ring-white/10"
                        >
                            @if (($product['stock_on_hand'] ?? 0) < 10)
                                <span class="absolute right-1 top-1 rounded-full bg-danger-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                    Low stock
                                </span>
                            @endif

                            @if (! empty($product['image_path']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product['image_path']) }}" alt="" class="h-20 w-full object-cover" loading="lazy" />
                            @else
                                <div class="flex h-20 w-full items-center justify-center bg-gray-200 text-gray-400 dark:bg-gray-700">
                                    <x-heroicon-o-cube class="h-8 w-8" />
                                </div>
                            @endif

                            <div class="p-2">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $product['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">KES {{ number_format($product['unit_price'], 2) }}</p>
                            </div>
                        </button>
                    @empty
                        @if (strlen($productSearch ?? '') >= 2)
                            <p class="col-span-full py-6 text-center text-sm text-gray-400">No products match "{{ $productSearch }}".</p>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ============== RIGHT: cart + customer + split payment ============== --}}
        <div class="space-y-4">

            {{-- Customer (optional) — needed for store credit redemption/issuance --}}
            <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Customer (optional)</label>
                @if ($customerId)
                    <div class="flex items-center justify-between rounded-lg bg-primary-50 px-3 py-2 text-sm dark:bg-primary-500/10">
                        <span class="text-primary-700 dark:text-primary-300">Customer attached</span>
                        <button type="button" wire:click="$set('customerId', null)" class="text-primary-600 hover:underline dark:text-primary-400">Remove</button>
                    </div>
                @else
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="customerSearch"
                        placeholder="Search by name or phone…"
                        class="fi-input block w-full rounded-lg border-none bg-gray-50 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-white/10"
                    />
                    @if (! empty($customerResults))
                        <ul class="mt-2 divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($customerResults as $customer)
                                <li>
                                    <button type="button" wire:click="selectCustomer('{{ $customer['id'] }}')" class="flex w-full items-center justify-between py-1.5 text-sm hover:text-primary-600">
                                        <span>{{ $customer['name'] }}</span>
                                        <span class="text-xs text-gray-400">credit KES {{ number_format($customer['store_credit_balance'], 2) }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>

            {{-- Cart --}}
            <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Cart</h3>

                @if (empty($cart))
                    {{-- Branded empty-state illustration rather than a bare "no items" string --}}
                    <div class="flex flex-col items-center py-10 text-center">
                        <svg viewBox="0 0 120 120" class="h-24 w-24 text-gray-300 dark:text-gray-700" fill="none">
                            <circle cx="60" cy="60" r="58" stroke="currentColor" stroke-width="3" stroke-dasharray="6 6" />
                            <path d="M38 46h44l-4 28a6 6 0 0 1-6 5H48a6 6 0 0 1-6-5l-4-28Z" stroke="currentColor" stroke-width="3" fill="none" />
                            <path d="M46 46v-6a14 14 0 0 1 28 0v6" stroke="currentColor" stroke-width="3" fill="none" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-400">Scan or tap a product to start a sale.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($cart as $index => $line)
                            <li class="flex items-center gap-3 py-3">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $line['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        KES {{ number_format($line['unit_price'], 2) }}
                                        @if ($line['has_promotion'])
                                            <span class="ml-1 rounded bg-success-50 px-1 py-0.5 text-[10px] font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">promo</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="decrementQty({{ $index }})" class="h-7 w-7 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300">−</button>
                                    <span class="w-6 text-center text-sm">{{ $line['quantity'] }}</span>
                                    <button type="button" wire:click="incrementQty({{ $index }})" class="h-7 w-7 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300">+</button>
                                </div>

                                <button type="button" wire:click="removeItem({{ $index }})" class="text-danger-500 hover:text-danger-600" title="Remove">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-base font-semibold text-gray-900 dark:border-white/10 dark:text-white">
                        <span>Total</span>
                        <span>KES {{ number_format($this->cartTotal, 2) }}</span>
                    </div>
                @endif
            </div>

            {{-- Split payment: any combination of cash / M-Pesa / card / store credit --}}
            <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Payment</h3>
                    <button type="button" wire:click="addPaymentRow" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">+ Add payment method</button>
                </div>

                <div class="space-y-2">
                    @foreach ($paymentLines as $index => $line)
                        <div class="flex items-center gap-2">
                            <select wire:model="paymentLines.{{ $index }}.method" class="fi-select rounded-lg border-none bg-gray-50 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-800 dark:text-white dark:ring-white/10">
                                <option value="cash">Cash</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="card">Card</option>
                                <option value="store_credit">Store credit</option>
                            </select>
                            <input type="number" step="0.01" wire:model="paymentLines.{{ $index }}.amount" placeholder="Amount"
                                   class="fi-input w-24 rounded-lg border-none bg-gray-50 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-800 dark:text-white dark:ring-white/10" />
                            @if ($line['method'] === 'mpesa')
                                <input type="text" wire:model="paymentLines.{{ $index }}.payer_phone" placeholder="2547XXXXXXXX"
                                       class="fi-input w-32 rounded-lg border-none bg-gray-50 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-800 dark:text-white dark:ring-white/10" />
                            @endif
                            @if (count($paymentLines) > 1)
                                <button type="button" wire:click="removePaymentRow({{ $index }})" class="text-gray-400 hover:text-danger-500">&times;</button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Amount tendered</span>
                    <span class="font-medium {{ $this->paymentTotal < $this->cartTotal ? 'text-danger-500' : 'text-success-600' }}">
                        KES {{ number_format($this->paymentTotal, 2) }}
                    </span>
                </div>

                <button
                    type="button"
                    wire:click="checkout"
                    wire:loading.attr="disabled"
                    @disabled(empty($cart) || $this->paymentTotal < $this->cartTotal)
                    class="fi-btn mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <span wire:loading.remove wire:target="checkout">Charge KES {{ number_format($this->cartTotal, 2) }}</span>
                    <span wire:loading wire:target="checkout">Processing…</span>
                </button>
            </div>

            @if ($lastCompletedOrder)
                <div class="rounded-xl bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
                    Sale {{ $lastCompletedOrder->invoice_number }} completed. Receipt is generating in the background —
                    it'll appear under this order's page shortly.
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>

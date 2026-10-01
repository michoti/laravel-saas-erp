<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $q = '';

    /** Swap for a real Help Center query once articles live in the database. */
    #[Computed]
    public function results(): array
    {
        $articles = [
            ['Prepare your products and prices', 'Get started', 'Clean names, KES prices, tax and stock-tracking settings.'],
            ['Record a sale and take M-Pesa payment', 'Point of Sale', 'Add items, apply promotions, send an STK push and confirm.'],
            ['Refund or correct an order', 'Point of Sale', 'Use the refund process instead of a compensating sale.'],
            ['Review stock movements', 'Inventory', 'Follow the ledger, spot low stock and record adjustments.'],
            ['Import products from a spreadsheet', 'Inventory', 'Prepare a clean file and check a sample after import.'],
            ['Invite staff and set roles', 'Access', 'Give each person only the access their job needs.'],
            ['Manage customer records', 'CRM', 'Avoid duplicates and keep only the details you need.'],
            ['Change plan or enable an app', 'Billing', 'Check the effect on access and billing first.'],
        ];

        $term = mb_strtolower(trim($this->q));

        return $term === '' ? $articles : array_values(array_filter(
            $articles,
            fn ($a) => str_contains(mb_strtolower(implode(' ', $a)), $term),
        ));
    }
};

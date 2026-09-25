<?php

declare(strict_types=1);

namespace Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSyncBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sanctum + tenancy middleware already gate this route;
        // add per-device throttling/ACL here if devices are individually scoped.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'uuid'],
            'device_id' => ['required', 'uuid'],
            'generated_at' => ['required', 'date'],

            'orders' => ['required', 'array', 'min:1'],
            'orders.*.id' => ['required', 'uuid'],
            'orders.*.local_reference' => ['nullable', 'string', 'max:64'],
            'orders.*.customer_id' => ['nullable', 'uuid'],
            'orders.*.cashier_user_id' => ['nullable', 'uuid'],
            'orders.*.warehouse_id' => ['nullable', 'uuid'],
            'orders.*.currency' => ['required', 'string', 'size:3'],
            'orders.*.order_date' => ['required', 'date'],
            'orders.*.client_created_at' => ['required', 'date'],
            'orders.*.client_updated_at' => ['required', 'date'],
            'orders.*.version' => ['required', 'integer', 'min:1'],

            'orders.*.items' => ['required', 'array', 'min:1'],
            'orders.*.items.*.id' => ['required', 'uuid'],
            'orders.*.items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'orders.*.items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'orders.*.items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'orders.*.items.*.tax_rate' => ['required', 'numeric', 'min:0'],
            'orders.*.items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],

            'orders.*.payment' => ['required', 'array'],
            'orders.*.payment.id' => ['required', 'uuid'],
            'orders.*.payment.method' => ['required', 'in:cash,mpesa,card,other'],
            'orders.*.payment.amount' => ['required', 'numeric', 'gt:0'],
            'orders.*.payment.payer_phone' => ['required_if:orders.*.payment.method,mpesa', 'nullable', 'string'],
        ];
    }
}

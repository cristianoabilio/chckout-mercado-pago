<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Order;

class OrderService
{
    public function update($orderId, $payment, $user, $address): Order
    {
        $order = Order::find($orderId);
        $order->user_id = $user->id;
        $order->status = OrderStatusEnum::parse(data_get($payment, 'status'));
        $order->save();

        $order->payments()->create([
            'external_id' => data_get($payment, 'id'),
            'method' => PaymentMethodEnum::parse(data_get($payment, 'payment_type_id')),
            'status' => PaymentStatusEnum::parse(data_get($payment, 'status')),
            'installments' => data_get($payment, 'installments'),
            'approved_at' => data_get($payment, 'date_approved'),
            'qr_code_64' => $payment?->point_of_interaction?->transaction_data?->qr_code_base64 ?? null,
            'qr_code' => $payment?->point_of_interaction?->transaction_data?->qr_code ?? null,
            'ticket_url' => $payment?->point_of_interaction?->transaction_data?->ticket_url ?? $payment?->transaction_details?->external_resource_url,
        ]);

        $order->shippings()->create([
            'address' => data_get($address, 'address'),
            'number' => data_get($address, 'number'),
            'complement' => data_get($address, 'complement'),
            'district' => data_get($address, 'district'),
            'city' => data_get($address, 'city'),
            'state' => data_get($address, 'state'),
            'zipcode' => data_get($address, 'zipcode'),
        ]);

        $order->load(['payments', 'shippings']);

        return $order;
    }
}
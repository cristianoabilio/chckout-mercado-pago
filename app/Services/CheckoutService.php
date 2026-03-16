<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Exceptions\PaymentException;
use App\Models\Order;
use Database\Seeders\OrderSeeder;
use Illuminate\Support\Str;
use MercadoPago\Payer;
use MercadoPago\Payment;
use MercadoPago\SDK;

class CheckoutService
{

    public function __construct()
    {
        SDK::setAccessToken(config('payment.mercadopago.access_token'));
    }

    public function loadCart(): array
    {
        $cart = Order::with('skus.product', 'skus.features')
            ->where('status', OrderStatusEnum::CART)
            ->where(function ($query) {
                $query->where('session_id', session()->getId());
                if (auth()->check()) {
                    $query->orWhere('user_id', auth()->user()->id);
                }
            })->first();

        if (! $cart && config('app.env') == 'local' || config('app.env') == 'testing') {
            $seed = new OrderSeeder();
            $seed->run(session()->getId());
            return $this->loadCart();
        }

        return $cart->toArray();
    }

    public function creditCardPayment($data, $user, $address)
    {
        $payment = new Payment();
        $payment->transaction_amount = (float) data_get($data, 'transaction_amount');
        $payment->token = data_get($data,'token');
        $payment->description = data_get($data,'description');
        $payment->installments = (int)data_get($data,'installments');
        $payment->payment_method_id = data_get($data,'payment_method_id');
        $payment->issuer_id = (int)data_get($data,'issuer_id');

        $payment->payer = $this->buildPayer($user, $address);

        $payment->save();

        throw_if(
            ! $payment->id || $payment->status === 'rejected',
            PaymentException::class,
            $payment?->error?->message ?? "Verifique os dados do cartão"
        );

        return $payment;
    }

    public function pixOrBankSlipPayment($data, $user, $address)
    {
        $payment = new Payment();
        $payment->transaction_amount = data_get($data, 'amount');
        $payment->description = "Título do produto";
        $payment->payment_method_id = data_get($data, 'method');
        $payment->payer = $this->buildPayer($user, $address);

        $payment->save();

        throw_if(
            ! $payment->id || $payment->status === 'rejected',
            PaymentException::class,
            $payment?->error?->message ?? "Verifique os dados do cartão"
        );

        return $payment;
    }

    public function buildPayer($user, $address)
    {
        $firstName = explode(' ', $user['name'])[0];
        return [
            "email" => data_get($user, 'email'),
            "first_name" => $firstName,
            "last_name" => Str::of(data_get($user, 'name'))->after($firstName)->trim(),
            "identification" => [
                "type" => "CPF",
                "number" => data_get($user, 'cpf')
            ],
            "address"=>  [
                "zip_code" => data_get($address, 'zipcode'),
                "street_name" => data_get($address, 'address'),
                "street_number" => data_get($address, 'number'),
                "neighborhood" => data_get($address, 'district'),
                "city" => data_get($address, 'city'),
                "federal_unit" => data_get($address, 'state')
            ]
        ];
    }

}
<?php

namespace App\Livewire\Forms;

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Rule;
use Livewire\Form;

class AddressForm extends Form
{
    #[Rule('required|string|max:9', message: 'Cep inválido')]
    public $zipcode = "";

    #[Rule('required|string|max:255')]
    public $address = "";

    #[Rule('required|string|max:255')]
    public $city = "";

    #[Rule('required|string|max:2')]
    public $state = "";

    #[Rule('required|string|max:255')]
    public $district = "";

    #[Rule('required|string|max:255')]
    public $number = "";

    #[Rule('nullable|string|max:255')]
    public $complement = "";

    public function findAddress()
    {
        $zipcode = preg_replace('/[^0-9]/im', '', $this->zipcode);
        $url = "https://viacep.com.br/ws/{$zipcode}/json/";
        $address = Http::get($url)->object();

        if (! $address || (isset($address->erro) && $address->erro)) {
            $this->addError('address.zipcode', 'Cep Inválido');
            return;
        }

        $this->address = data_get($address, 'logradouro');
        $this->city = data_get($address, 'localidade');
        $this->state = data_get($address, 'uf');
        $this->district = data_get($address, 'bairro');
    }
}
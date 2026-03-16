<?php

use App\Enums\CheckoutStepsEnum;
use App\Livewire\Checkout;
use Livewire\Livewire;

it('checkout is rendering', function (){
    Livewire::test(Checkout::class)
        ->assertStatus(200);
});

it('if user validation is working', function() {
    Livewire::test(Checkout::class)
        ->set('user.name', '')
        ->set('user.email', '')
        ->call('submitInformationStep')
        ->assertHasErrors(['user.*']);
});

it('if address validation is working', function() {
    Livewire::test(Checkout::class)
        ->set('user.name', 'John Doe')
        ->set('user.email', 'teste@teste.com')
        ->set('address.address', '')
        ->call('submitInformationStep')
        ->assertHasErrors(['address.*']);
});

test('if user can move forward the shipping step', function() {
    Livewire::test(Checkout::class)
        ->set('user.name', fake()->name())
        ->set('user.email', fake()->email())
        ->set('address.zipcode', "13720-000")
        ->set('address.address', fake()->streetAddress())
        ->set('address.district', fake()->word())
        ->set('address.city', fake()->city())
        ->set('address.number', "123")
        ->set('address.state', fake()->stateAbbr())
        ->call('submitInformationStep')
        ->assertSet('step', CheckoutStepsEnum::SHIPPING->value);
});

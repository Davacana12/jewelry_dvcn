<?php

use App\EstadoApartado;
use App\Models\Apartado;
use App\Models\Product;

test('active apartados past their deadline expire and return their pieces to stock', function () {
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->pastDeadline()->create(['cantidad' => 2]);

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Vencido)
        ->and($product->refresh()->Stock)->toBe(2);
});

test('apartados on their deadline day do not expire yet', function () {
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->create(['fecha_limite' => today()]);

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Activo)
        ->and($product->refresh()->Stock)->toBe(0);
});

test('apartados that are not active are left untouched', function () {
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->liquidado()->pastDeadline()->create();

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Liquidado)
        ->and($product->refresh()->Stock)->toBe(0);
});

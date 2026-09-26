<?php

use App\EstadoApartado;
use App\Models\Apartado;
use App\Models\Product;

test('active apartados past their deadline are marked at risk without touching stock', function () {
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->create(['fecha_limite' => today()->subDay()]);

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::EnRiesgo)
        ->and($product->refresh()->Stock)->toBe(0);
});

test('apartados stay at risk until the last grace day', function () {
    $apartado = Apartado::factory()->enRiesgo()->create(['fecha_limite' => today()->subDays(3)]);

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::EnRiesgo);
});

test('open apartados past the grace period expire and return their pieces to stock', function (string $estado) {
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->pastGracePeriod()->create([
        'cantidad' => 2,
        'estado' => $estado,
    ]);

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Vencido)
        ->and($product->refresh()->Stock)->toBe(2);
})->with(['activo', 'en_riesgo']);

test('apartados on their deadline day are left active', function () {
    $apartado = Apartado::factory()->create(['fecha_limite' => today()]);

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Activo);
});

test('apartados that are not open are left untouched', function () {
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->liquidado()->pastGracePeriod()->create();

    $this->artisan('apartados:vencer')->assertSuccessful();

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Liquidado)
        ->and($product->refresh()->Stock)->toBe(0);
});

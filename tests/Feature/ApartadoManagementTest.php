<?php

use App\EstadoApartado;
use App\Models\Abono;
use App\Models\Apartado;
use App\Models\Product;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('apartados.index'))->assertRedirect(route('login'));
});

test('apartados can be searched by client name', function () {
    $user = User::factory()->create();
    Apartado::factory()->create(['nombre_cliente' => 'María López']);
    Apartado::factory()->create(['nombre_cliente' => 'Juan Pérez']);

    $this->actingAs($user)
        ->get(route('apartados.index', ['buscar' => 'María']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apartados/index')
            ->has('apartados.data', 1)
            ->where('apartados.data.0.nombre_cliente', 'María López'));
});

test('the list shows the paid and pending balance and fines of each apartado', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['Cost' => 600]);
    $apartado = Apartado::factory()->for($product, 'producto')->create(['cantidad' => 1, 'anticipo' => 120]);
    Abono::factory()->for($apartado)->create(['monto' => 80, 'multa' => 9.6]);

    $this->actingAs($user)
        ->get(route('apartados.index'))
        ->assertInertia(fn ($page) => $page
            ->where('apartados.data.0.saldo_pagado', '200.00')
            ->where('apartados.data.0.saldo_pendiente', '400.00')
            ->where('apartados.data.0.multas', '9.60'));
});

test('an apartado is created with the computed total and reserves stock', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    $product = Product::factory()->create(['Cost' => 300, 'Stock' => 5]);

    $this->actingAs($user)
        ->post(route('apartados.store'), [
            'nombre_cliente' => 'María López',
            'telefono_cliente' => '5512345678',
            'producto_id' => $product->idProduct,
            'cantidad' => 2,
            'anticipo' => 120,
        ])
        ->assertSessionHasNoErrors();

    $apartado = Apartado::sole();

    expect($apartado)
        ->total_compra->toBe('600.00')
        ->anticipo->toBe('120.00')
        ->estado->toBe(EstadoApartado::Activo)
        ->and($apartado->fecha_limite->toDateString())->toBe(today()->addDays(15)->toDateString())
        ->and($product->refresh()->Stock)->toBe(3);
});

test('a down payment covering the total creates a settled apartado', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['Cost' => 600, 'Stock' => 1]);

    $this->actingAs($user)
        ->post(route('apartados.store'), [
            'nombre_cliente' => 'María López',
            'telefono_cliente' => '5512345678',
            'producto_id' => $product->idProduct,
            'cantidad' => 1,
            'anticipo' => 600,
        ])
        ->assertSessionHasNoErrors();

    expect(Apartado::sole()->estado)->toBe(EstadoApartado::Liquidado)
        ->and($product->refresh()->Stock)->toBe(0);
});

test('invalid apartados are rejected', function (array $overrides, string $field, string $message) {
    $user = User::factory()->create();
    $product = Product::factory()->create(['Cost' => 600, 'Stock' => 2]);

    $this->actingAs($user)
        ->post(route('apartados.store'), [
            'nombre_cliente' => 'María López',
            'telefono_cliente' => '5512345678',
            'producto_id' => $product->idProduct,
            'cantidad' => 1,
            'anticipo' => 120,
            ...$overrides,
        ])
        ->assertSessionHasErrors([$field => $message]);

    expect(Apartado::count())->toBe(0)
        ->and($product->refresh()->Stock)->toBe(2);
})->with([
    'down payment below 20%' => [['anticipo' => 119.99], 'anticipo', 'El anticipo mínimo es de $120.00 (20% del total).'],
    'down payment above the total' => [['anticipo' => 600.01], 'anticipo', 'El anticipo no puede ser mayor al total de $600.00.'],
    'more pieces than in stock' => [['cantidad' => 3], 'cantidad', 'Solo hay 2 piezas disponibles.'],
    'invalid phone' => [['telefono_cliente' => 'abc'], 'telefono_cliente', 'El teléfono debe tener entre 7 y 20 dígitos.'],
]);

test('a sold out product cannot be put on layaway', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['Stock' => 0]);

    $this->actingAs($user)
        ->post(route('apartados.store'), [
            'nombre_cliente' => 'María López',
            'telefono_cliente' => '5512345678',
            'producto_id' => $product->idProduct,
            'cantidad' => 1,
            'anticipo' => 1000,
        ])
        ->assertSessionHasErrors(['cantidad' => 'Este producto está agotado.']);
});

test('empty fields show an error for each one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('apartados.store'), [])
        ->assertSessionHasErrors([
            'nombre_cliente' => 'El nombre del cliente es obligatorio.',
            'telefono_cliente' => 'El teléfono del cliente es obligatorio.',
            'producto_id' => 'Selecciona un producto.',
            'cantidad' => 'La cantidad es obligatoria.',
            'anticipo' => 'El anticipo es obligatorio.',
        ]);
});

test('cancelling an open apartado returns its pieces to stock', function (string $estado) {
    $user = User::factory()->create();
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->create(['cantidad' => 2, 'estado' => $estado]);

    $this->actingAs($user)
        ->patch(route('apartados.cancel', $apartado))
        ->assertRedirect(route('apartados.show', $apartado));

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Cancelado)
        ->and($product->refresh()->Stock)->toBe(2);
})->with(['activo', 'en_riesgo']);

test('closed apartados cannot be cancelled', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['Stock' => 0]);
    $apartado = Apartado::factory()->for($product, 'producto')->liquidado()->create();

    $this->actingAs($user)->patch(route('apartados.cancel', $apartado));

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Liquidado)
        ->and($product->refresh()->Stock)->toBe(0);
});

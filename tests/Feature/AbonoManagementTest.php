<?php

use App\EstadoApartado;
use App\Models\Apartado;
use App\Models\Product;
use App\Models\User;

/**
 * An active apartado of $600 with a $120 down payment, leaving $480 to pay.
 */
function apartadoDe600(): Apartado
{
    return Apartado::factory()
        ->for(Product::factory()->state(['Cost' => 600]), 'producto')
        ->create(['cantidad' => 1, 'anticipo' => 120]);
}

test('a payment below 20% of the total is accepted', function () {
    $user = User::factory()->create();
    $apartado = apartadoDe600();

    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 50])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('apartados.show', $apartado));

    expect($apartado->abonos()->sole()->monto)->toBe('50.00')
        ->and($apartado->refresh()->estado)->toBe(EstadoApartado::Activo);
});

test('paying off the balance settles the apartado', function () {
    $user = User::factory()->create();
    $apartado = apartadoDe600();

    $this->actingAs($user)->post(route('apartados.abonos.store', $apartado), ['monto' => 400]);
    $this->actingAs($user)->post(route('apartados.abonos.store', $apartado), ['monto' => 80]);

    expect($apartado->refresh()->estado)->toBe(EstadoApartado::Liquidado)
        ->and($apartado->saldoPendienteCents())->toBe(0);
});

test('invalid payments are rejected', function (mixed $monto, string $message) {
    $user = User::factory()->create();
    $apartado = apartadoDe600();

    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => $monto])
        ->assertSessionHasErrors(['monto' => $message]);

    expect($apartado->abonos()->count())->toBe(0);
})->with([
    'empty' => ['', 'El monto del abono es obligatorio.'],
    'zero' => [0, 'El monto debe ser mayor a 0.'],
    'above the balance' => [480.01, 'El abono no puede ser mayor al saldo pendiente de $480.00.'],
]);

test('payments are rejected on apartados that are not active', function () {
    $user = User::factory()->create();
    $apartado = Apartado::factory()->cancelado()->create();

    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 10])
        ->assertSessionHasErrors(['monto' => 'Solo se pueden registrar abonos en apartados activos.']);
});

test('payments are accepted on the deadline day and rejected after it', function () {
    $user = User::factory()->create();
    $apartado = apartadoDe600();

    $this->travelTo($apartado->fecha_limite->copy()->setTime(18, 0));
    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 10])
        ->assertSessionHasNoErrors();

    $this->travelTo($apartado->fecha_limite->copy()->addDay());
    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 10])
        ->assertSessionHasErrors(['monto' => 'La fecha límite de este apartado ya pasó.']);
});

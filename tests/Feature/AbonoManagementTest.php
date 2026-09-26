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

test('payments are rejected on apartados that are not open', function () {
    $user = User::factory()->create();
    $apartado = Apartado::factory()->cancelado()->create();

    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 10])
        ->assertSessionHasErrors(['monto' => 'Solo se pueden registrar abonos en apartados activos o en riesgo.']);
});

test('a payment on the deadline day has no fine', function () {
    $user = User::factory()->create();
    $apartado = apartadoDe600();

    $this->travelTo($apartado->fecha_limite->setTime(18, 0));
    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 100])
        ->assertSessionHasNoErrors();

    expect($apartado->abonos()->sole()->multa)->toBe('0.00')
        ->and($apartado->refresh()->estado)->toBe(EstadoApartado::Activo);
});

test('a payment during the grace period pays a 2% fine and starts a new 15 day period', function () {
    $user = User::factory()->create();
    $apartado = apartadoDe600();
    $apartado->update(['estado' => EstadoApartado::EnRiesgo]);
    $fechaLimite = $apartado->fecha_limite;

    $this->travelTo($fechaLimite->addDays(3)->setTime(12, 0));
    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 100])
        ->assertSessionHasNoErrors();

    expect($apartado->abonos()->sole())
        ->monto->toBe('100.00')
        ->multa->toBe('9.60')
        ->and($apartado->refresh()->estado)->toBe(EstadoApartado::Activo)
        ->and($apartado->fecha_limite->toDateString())->toBe($fechaLimite->addDays(15)->toDateString())
        ->and($apartado->saldoPendienteCents())->toBe(38000);
});

test('paying off the balance during the grace period still pays the fine', function () {
    $user = User::factory()->create();
    $apartado = apartadoDe600();

    $this->travelTo($apartado->fecha_limite->addDay());
    $this->actingAs($user)->post(route('apartados.abonos.store', $apartado), ['monto' => 480]);

    expect($apartado->abonos()->sole()->multa)->toBe('9.60')
        ->and($apartado->refresh()->estado)->toBe(EstadoApartado::Liquidado);
});

test('payments are rejected once the grace period is over', function () {
    $user = User::factory()->create();
    $apartado = apartadoDe600();

    $this->travelTo($apartado->fecha_limite->addDays(4));
    $this->actingAs($user)
        ->post(route('apartados.abonos.store', $apartado), ['monto' => 100])
        ->assertSessionHasErrors('monto');

    expect($apartado->abonos()->count())->toBe(0);
});

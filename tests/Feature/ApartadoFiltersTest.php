<?php

use App\Models\Apartado;
use App\Models\Product;
use App\Models\User;

test('apartados can be searched by phone number', function () {
    $user = User::factory()->create();
    Apartado::factory()->create(['nombre_cliente' => 'María López', 'telefono_cliente' => '5512345678']);
    Apartado::factory()->create(['nombre_cliente' => 'Juan Pérez', 'telefono_cliente' => '3398765432']);

    $this->actingAs($user)
        ->get(route('apartados.index', ['buscar' => '5512']))
        ->assertInertia(fn ($page) => $page
            ->has('apartados.data', 1)
            ->where('apartados.data.0.nombre_cliente', 'María López'));
});

test('apartados can be filtered by status', function () {
    $user = User::factory()->create();
    Apartado::factory()->create(['nombre_cliente' => 'Activo']);
    Apartado::factory()->liquidado()->create(['nombre_cliente' => 'Liquidado']);

    $this->actingAs($user)
        ->get(route('apartados.index', ['estado' => 'liquidado']))
        ->assertInertia(fn ($page) => $page
            ->has('apartados.data', 1)
            ->where('apartados.data.0.nombre_cliente', 'Liquidado')
            ->where('filters.estado', 'liquidado'));
});

test('apartados can be filtered by product', function () {
    $user = User::factory()->create();
    $anillo = Product::factory()->create();
    Apartado::factory()->for($anillo, 'producto')->create(['nombre_cliente' => 'Con anillo']);
    Apartado::factory()->create(['nombre_cliente' => 'Otro producto']);

    $this->actingAs($user)
        ->get(route('apartados.index', ['producto_id' => $anillo->idProduct]))
        ->assertInertia(fn ($page) => $page
            ->has('apartados.data', 1)
            ->where('apartados.data.0.nombre_cliente', 'Con anillo'));
});

test('apartados can be filtered by date range including both ends', function () {
    $user = User::factory()->create();
    Apartado::factory()->create(['nombre_cliente' => 'Antes', 'fecha_apartada' => '2026-09-09 23:00:00']);
    Apartado::factory()->create(['nombre_cliente' => 'Primer día', 'fecha_apartada' => '2026-09-10 08:00:00']);
    Apartado::factory()->create(['nombre_cliente' => 'Último día', 'fecha_apartada' => '2026-09-20 21:00:00']);
    Apartado::factory()->create(['nombre_cliente' => 'Después', 'fecha_apartada' => '2026-09-21 01:00:00']);

    $this->actingAs($user)
        ->get(route('apartados.index', ['desde' => '2026-09-10', 'hasta' => '2026-09-20', 'orden' => 'antiguos']))
        ->assertInertia(fn ($page) => $page
            ->has('apartados.data', 2)
            ->where('apartados.data.0.nombre_cliente', 'Primer día')
            ->where('apartados.data.1.nombre_cliente', 'Último día'));
});

test('apartados can be sorted by the closest deadline', function () {
    $user = User::factory()->create();
    Apartado::factory()->create(['nombre_cliente' => 'Vence después', 'fecha_limite' => today()->addDays(10)]);
    Apartado::factory()->create(['nombre_cliente' => 'Vence pronto', 'fecha_limite' => today()->addDays(2)]);

    $this->actingAs($user)
        ->get(route('apartados.index', ['orden' => 'fecha_limite']))
        ->assertInertia(fn ($page) => $page
            ->where('apartados.data.0.nombre_cliente', 'Vence pronto')
            ->where('apartados.data.1.nombre_cliente', 'Vence después'));
});

test('invalid filters are rejected', function (array $query, string $field, string $message) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('apartados.index', $query))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'end date before start date' => [['desde' => '2026-09-20', 'hasta' => '2026-09-10'], 'hasta', 'La fecha "hasta" debe ser igual o posterior a la fecha "desde".'],
    'unknown status' => [['estado' => 'perdido'], 'estado', 'El estado seleccionado no es válido.'],
    'unknown sort' => [['orden' => 'precio'], 'orden', 'El orden seleccionado no es válido.'],
]);

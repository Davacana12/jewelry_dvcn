<?php

use App\Models\Apartado;
use App\Models\Product;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('products.index'))->assertRedirect(route('login'));
});

test('products page is displayed', function () {
    $user = User::factory()->create();
    Product::factory()->create(['Name_Product' => 'Anillo de plata']);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/index')
            ->where('products.data.0.Name_Product', 'Anillo de plata'));
});

test('a product can be created', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'Name_Product' => 'Collar de oro',
            'Cost' => 1500.5,
            'Stock' => 1,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('Product', [
        'Name_Product' => 'Collar de oro',
        'Cost' => 1500.5,
        'Stock' => 1,
    ]);
});

test('empty fields show an error for each one', function (string $method, string $routeName) {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->{$method}(route($routeName, $product), [
            'Name_Product' => '',
            'Cost' => '',
            'Stock' => '',
        ])
        ->assertSessionHasErrors([
            'Name_Product' => 'El nombre del producto es obligatorio.',
            'Cost' => 'El costo es obligatorio.',
            'Stock' => 'El stock es obligatorio.',
        ]);
})->with([
    'create' => ['post', 'products.store'],
    'update' => ['put', 'products.update'],
]);

test('invalid values are rejected when creating', function (string $field, mixed $value, string $message) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'Name_Product' => 'Collar de oro',
            'Cost' => 100,
            'Stock' => 5,
            $field => $value,
        ])
        ->assertSessionHasErrors([$field => $message]);

    expect(Product::count())->toBe(0);
})->with([
    'name shorter than 5 characters' => ['Name_Product', 'Anil', 'El nombre del producto debe tener al menos 5 caracteres.'],
    'name longer than 255 characters' => ['Name_Product', str_repeat('a', 256), 'El nombre del producto no puede tener más de 255 caracteres.'],
    'cost of zero' => ['Cost', 0, 'El costo debe ser mayor a 0.'],
    'negative cost' => ['Cost', -10, 'El costo debe ser mayor a 0.'],
    'stock of zero' => ['Stock', 0, 'El stock debe ser al menos 1.'],
    'negative stock' => ['Stock', -1, 'El stock debe ser al menos 1.'],
]);

test('a product can be updated', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'Name_Product' => 'Pulsera de plata',
            'Cost' => 250,
            'Stock' => 1,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'));

    expect($product->refresh())
        ->Name_Product->toBe('Pulsera de plata')
        ->Cost->toBe(250.0)
        ->Stock->toBe(1);
});

test('invalid values are rejected when updating', function (string $field, mixed $value, string $message) {
    $user = User::factory()->create();
    $product = Product::factory()->create(['Name_Product' => 'Collar de oro']);

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'Name_Product' => 'Collar de plata',
            'Cost' => 100,
            'Stock' => 5,
            $field => $value,
        ])
        ->assertSessionHasErrors([$field => $message]);

    expect($product->refresh()->Name_Product)->toBe('Collar de oro');
})->with([
    'name shorter than 5 characters' => ['Name_Product', 'Anil', 'El nombre del producto debe tener al menos 5 caracteres.'],
    'name longer than 255 characters' => ['Name_Product', str_repeat('a', 256), 'El nombre del producto no puede tener más de 255 caracteres.'],
    'cost of zero' => ['Cost', 0, 'El costo debe ser mayor a 0.'],
    'negative cost' => ['Cost', -10, 'El costo debe ser mayor a 0.'],
    'stock of zero' => ['Stock', 0, 'El stock debe ser al menos 1.'],
    'negative stock' => ['Stock', -1, 'El stock debe ser al menos 1.'],
]);

test('a product with apartados cannot be deleted', function () {
    $user = User::factory()->create();
    $apartado = Apartado::factory()->create();

    $this->actingAs($user)
        ->delete(route('products.destroy', $apartado->producto))
        ->assertRedirect(route('products.index'));

    expect($apartado->producto->fresh())->not->toBeNull();
});

test('a product can be deleted', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect($product->fresh())->toBeNull();
});

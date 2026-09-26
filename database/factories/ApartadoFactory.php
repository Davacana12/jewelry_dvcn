<?php

namespace Database\Factories;

use App\EstadoApartado;
use App\Models\Apartado;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartado>
 */
class ApartadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_cliente' => fake()->name(),
            'telefono_cliente' => fake()->numerify('##########'),
            'producto_id' => Product::factory(),
            'cantidad' => fake()->numberBetween(1, 3),
            'total_compra' => fn (array $attributes) => Apartado::totalCentsFor(
                Product::query()->whereKey($attributes['producto_id'])->firstOrFail(),
                $attributes['cantidad'],
            ) / 100,
            'anticipo' => fn (array $attributes) => Apartado::anticipoMinimoCents(
                Apartado::toCents($attributes['total_compra']),
            ) / 100,
            'fecha_apartada' => now(),
            'fecha_limite' => today()->addDays(Apartado::DIAS_PARA_PAGAR),
            'estado' => EstadoApartado::Activo,
        ];
    }

    /**
     * Indicate that the apartado has been fully paid.
     */
    public function liquidado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoApartado::Liquidado,
        ]);
    }

    /**
     * Indicate that the apartado has been cancelled.
     */
    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoApartado::Cancelado,
        ]);
    }

    /**
     * Indicate that the deadline passed yesterday, so the apartado is in its grace period.
     */
    public function enRiesgo(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_apartada' => now()->subDays(Apartado::DIAS_PARA_PAGAR + 1),
            'fecha_limite' => today()->subDay(),
            'estado' => EstadoApartado::EnRiesgo,
        ]);
    }

    /**
     * Indicate that the grace period after the deadline is over.
     */
    public function pastGracePeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_apartada' => now()->subDays(Apartado::DIAS_PARA_PAGAR + Apartado::DIAS_DE_GRACIA + 1),
            'fecha_limite' => today()->subDays(Apartado::DIAS_DE_GRACIA + 1),
        ]);
    }
}

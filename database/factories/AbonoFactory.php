<?php

namespace Database\Factories;

use App\Models\Abono;
use App\Models\Apartado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Abono>
 */
class AbonoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_apartado' => Apartado::factory(),
            'monto' => fake()->randomFloat(2, 50, 1000),
            'fecha_monto' => now(),
        ];
    }
}

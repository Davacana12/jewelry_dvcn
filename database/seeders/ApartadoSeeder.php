<?php

namespace Database\Seeders;

use App\Models\Apartado;
use Illuminate\Database\Seeder;

class ApartadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Apartado::factory(10)->create();
    }
}

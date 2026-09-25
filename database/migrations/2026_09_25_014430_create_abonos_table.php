<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Abonos', function (Blueprint $table) {
            $table->id('id_abono');
            $table->foreignId('id_apartado')
                ->constrained('Apartados', 'id_apartados')
                ->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->timestamp('fecha_monto')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Abonos');
    }
};

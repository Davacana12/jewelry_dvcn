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
        Schema::create('Apartados', function (Blueprint $table) {
            $table->id('id_apartados');
            $table->string('nombre_cliente', 255);
            $table->string('telefono_cliente', 20);
            $table->foreignId('producto_id')
                ->constrained('Product', 'idProduct')
                ->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('total_compra', 10, 2);
            $table->decimal('anticipo', 10, 2);
            $table->timestamp('fecha_apartada')->useCurrent();
            $table->date('fecha_limite');
            $table->enum('estado', ['activo', 'liquidado', 'vencido', 'cancelado'])->default('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Apartados');
    }
};

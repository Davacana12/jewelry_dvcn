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
        Schema::table('Apartados', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'en_riesgo', 'liquidado', 'vencido', 'cancelado'])
                ->default('activo')
                ->change();
        });

        Schema::table('Abonos', function (Blueprint $table) {
            $table->decimal('multa', 10, 2)->default(0)->after('monto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Abonos', function (Blueprint $table) {
            $table->dropColumn('multa');
        });

        Schema::table('Apartados', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'liquidado', 'vencido', 'cancelado'])
                ->default('activo')
                ->change();
        });
    }
};

<?php

namespace App\Console\Commands;

use App\EstadoApartado;
use App\Models\Apartado;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('apartados:vencer')]
#[Description('Mark active apartados past their deadline as expired and return their pieces to stock')]
class VencerApartados extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $vencidos = 0;

        Apartado::query()
            ->where('estado', EstadoApartado::Activo)
            ->whereDate('fecha_limite', '<', today())
            ->eachById(function (Apartado $apartado) use (&$vencidos): void {
                DB::transaction(function () use ($apartado, &$vencidos): void {
                    $apartado = Apartado::query()->lockForUpdate()->findOrFail($apartado->id_apartados);

                    if (! $apartado->isActivo()) {
                        return;
                    }

                    $apartado->update(['estado' => EstadoApartado::Vencido]);
                    Product::query()->whereKey($apartado->producto_id)->increment('Stock', $apartado->cantidad);

                    $vencidos++;
                });
            }, column: 'id_apartados');

        $this->info("Apartados vencidos: {$vencidos}");

        return self::SUCCESS;
    }
}

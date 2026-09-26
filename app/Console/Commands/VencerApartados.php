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
#[Description('Mark apartados past their deadline as at risk, and expire those past the grace period, returning their pieces to stock')]
class VencerApartados extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $enRiesgo = Apartado::query()
            ->where('estado', EstadoApartado::Activo)
            ->whereDate('fecha_limite', '<', today())
            ->whereDate('fecha_limite', '>=', today()->subDays(Apartado::DIAS_DE_GRACIA))
            ->update(['estado' => EstadoApartado::EnRiesgo]);

        $vencidos = 0;

        Apartado::query()
            ->whereIn('estado', [EstadoApartado::Activo, EstadoApartado::EnRiesgo])
            ->whereDate('fecha_limite', '<', today()->subDays(Apartado::DIAS_DE_GRACIA))
            ->eachById(function (Apartado $apartado) use (&$vencidos): void {
                DB::transaction(function () use ($apartado, &$vencidos): void {
                    $apartado = Apartado::query()->lockForUpdate()->findOrFail($apartado->id_apartados);

                    if (! $apartado->isVigente() || ! $apartado->isPastGracePeriod()) {
                        return;
                    }

                    $apartado->update(['estado' => EstadoApartado::Vencido]);
                    Product::query()->whereKey($apartado->producto_id)->increment('Stock', $apartado->cantidad);

                    $vencidos++;
                });
            }, column: 'id_apartados');

        $this->info("Apartados en riesgo: {$enRiesgo}. Apartados vencidos: {$vencidos}.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers;

use App\EstadoApartado;
use App\Http\Requests\StoreAbonoRequest;
use App\Models\Abono;
use App\Models\Apartado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AbonoController extends Controller
{
    /**
     * Register a payment, charging a fine during the grace period, and settle
     * the apartado once it is fully paid.
     */
    public function store(StoreAbonoRequest $request, Apartado $apartado): RedirectResponse
    {
        [$abono, $apartado] = DB::transaction(function () use ($request, $apartado): array {
            $apartado = Apartado::query()->lockForUpdate()->findOrFail($apartado->id_apartados);
            $montoCents = Apartado::toCents($request->input('monto'));
            $saldoCents = $apartado->saldoPendienteCents();

            if (! $apartado->isVigente() || $apartado->isPastGracePeriod() || $montoCents > $saldoCents) {
                throw ValidationException::withMessages([
                    'monto' => 'El apartado cambió mientras se registraba el abono. Revisa el saldo e intenta de nuevo.',
                ]);
            }

            $enGracia = $apartado->isInGracePeriod();

            $abono = $apartado->abonos()->create([
                'monto' => $montoCents / 100,
                'multa' => ($enGracia ? Apartado::multaCentsFor($saldoCents) : 0) / 100,
                'fecha_monto' => now(),
            ]);

            if ($montoCents === $saldoCents) {
                $apartado->update(['estado' => EstadoApartado::Liquidado]);
            } elseif ($enGracia) {
                $apartado->update([
                    'estado' => EstadoApartado::Activo,
                    'fecha_limite' => $apartado->fecha_limite->addDays(Apartado::DIAS_PARA_PAGAR),
                ]);
            }

            return [$abono, $apartado];
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->successMessage($abono, $apartado)]);

        return to_route('apartados.show', $apartado);
    }

    /**
     * Describe what the payment did to the apartado.
     */
    private function successMessage(Abono $abono, Apartado $apartado): string
    {
        $message = $apartado->estado === EstadoApartado::Liquidado
            ? 'Abono registrado. ¡El apartado quedó liquidado!'
            : 'Abono registrado.';

        if (Apartado::toCents($abono->multa) === 0) {
            return $message;
        }

        $message .= sprintf(' Se cobró una multa de $%s por pago tardío.', number_format((float) $abono->multa, 2));

        if ($apartado->estado === EstadoApartado::Activo) {
            $message .= sprintf(' Nueva fecha límite: %s.', $apartado->fecha_limite->format('d/m/Y'));
        }

        return $message;
    }
}

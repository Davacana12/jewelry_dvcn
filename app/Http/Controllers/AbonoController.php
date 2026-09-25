<?php

namespace App\Http\Controllers;

use App\EstadoApartado;
use App\Http\Requests\StoreAbonoRequest;
use App\Models\Apartado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AbonoController extends Controller
{
    /**
     * Register a payment and settle the apartado once it is fully paid.
     */
    public function store(StoreAbonoRequest $request, Apartado $apartado): RedirectResponse
    {
        $liquidado = DB::transaction(function () use ($request, $apartado): bool {
            $apartado = Apartado::query()->lockForUpdate()->findOrFail($apartado->id_apartados);
            $montoCents = Apartado::toCents($request->input('monto'));

            if (! $apartado->isActivo() || $apartado->isPastDeadline() || $montoCents > $apartado->saldoPendienteCents()) {
                throw ValidationException::withMessages([
                    'monto' => 'El apartado cambió mientras se registraba el abono. Revisa el saldo e intenta de nuevo.',
                ]);
            }

            $apartado->abonos()->create([
                'monto' => $montoCents / 100,
                'fecha_monto' => now(),
            ]);

            if ($apartado->unsetRelation('abonos')->saldoPendienteCents() === 0) {
                $apartado->update(['estado' => EstadoApartado::Liquidado]);

                return true;
            }

            return false;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $liquidado
            ? 'Abono registrado. ¡El apartado quedó liquidado!'
            : 'Abono registrado.']);

        return to_route('apartados.show', $apartado);
    }
}

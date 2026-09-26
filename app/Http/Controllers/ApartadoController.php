<?php

namespace App\Http\Controllers;

use App\EstadoApartado;
use App\Http\Requests\FilterApartadosRequest;
use App\Http\Requests\StoreApartadoRequest;
use App\Models\Apartado;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApartadoController extends Controller
{
    /**
     * Show the list of apartados, filtered by the query string.
     */
    public function index(FilterApartadosRequest $request): Response
    {
        $filters = $request->filters();

        $apartados = Apartado::query()
            ->with('producto:idProduct,Name_Product')
            ->withSum('abonos', 'monto')
            ->withSum('abonos', 'multa')
            ->filter($filters)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Apartado $apartado) => [
                ...$apartado->toArray(),
                ...$this->saldos($apartado),
            ]);

        return Inertia::render('apartados/index', [
            'apartados' => $apartados,
            'filters' => array_map(fn (mixed $value): string => (string) $value, $filters),
            'products' => Product::query()
                ->orderBy('Name_Product')
                ->get(['idProduct', 'Name_Product']),
        ]);
    }

    /**
     * Show the form for creating a new apartado.
     */
    public function create(): Response
    {
        return Inertia::render('apartados/create', [
            'products' => Product::query()
                ->where('Stock', '>', 0)
                ->orderBy('Name_Product')
                ->get(['idProduct', 'Name_Product', 'Cost', 'Stock']),
            'diasParaPagar' => Apartado::DIAS_PARA_PAGAR,
            'porcentajeAnticipoMinimo' => Apartado::PORCENTAJE_ANTICIPO_MINIMO,
        ]);
    }

    /**
     * Store a new apartado and reserve its pieces from the product's stock.
     */
    public function store(StoreApartadoRequest $request): RedirectResponse
    {
        $apartado = DB::transaction(function () use ($request): Apartado {
            $product = Product::query()->lockForUpdate()->findOrFail($request->integer('producto_id'));
            $cantidad = $request->integer('cantidad');

            if ($cantidad > $product->Stock) {
                throw ValidationException::withMessages([
                    'cantidad' => "Solo hay {$product->Stock} piezas disponibles.",
                ]);
            }

            $totalCents = Apartado::totalCentsFor($product, $cantidad);
            $anticipoCents = Apartado::toCents($request->input('anticipo'));

            $product->decrement('Stock', $cantidad);

            return Apartado::create([
                ...$request->safe()->only(['nombre_cliente', 'telefono_cliente', 'producto_id', 'cantidad']),
                'total_compra' => $totalCents / 100,
                'anticipo' => $anticipoCents / 100,
                'fecha_apartada' => now(),
                'fecha_limite' => today()->addDays(Apartado::DIAS_PARA_PAGAR),
                'estado' => $anticipoCents === $totalCents ? EstadoApartado::Liquidado : EstadoApartado::Activo,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $apartado->estado === EstadoApartado::Liquidado
            ? 'Apartado registrado y liquidado.'
            : 'Apartado registrado.']);

        return to_route('apartados.show', $apartado);
    }

    /**
     * Show the given apartado with its payment history.
     */
    public function show(Apartado $apartado): Response
    {
        $apartado->load(['producto', 'abonos' => fn ($query) => $query->latest('fecha_monto')->latest('id_abono')]);

        $saldoCents = $apartado->saldoPendienteCents();
        $enGracia = $apartado->isVigente() && $apartado->isInGracePeriod();

        return Inertia::render('apartados/show', [
            'apartado' => [
                ...$apartado->toArray(),
                'saldo_pendiente' => number_format($saldoCents / 100, 2, '.', ''),
                'acepta_abonos' => $apartado->isVigente() && ! $apartado->isPastGracePeriod(),
                'en_gracia' => $enGracia,
                'plazo_vencido' => $apartado->isVigente() && $apartado->isPastGracePeriod(),
                'fecha_fin_gracia' => $apartado->fechaFinDeGracia()->toDateString(),
                'multa_si_abona' => number_format(($enGracia ? Apartado::multaCentsFor($saldoCents) : 0) / 100, 2, '.', ''),
            ],
            'reglas' => [
                'diasParaPagar' => Apartado::DIAS_PARA_PAGAR,
                'diasDeGracia' => Apartado::DIAS_DE_GRACIA,
                'porcentajeMulta' => Apartado::PORCENTAJE_MULTA,
            ],
        ]);
    }

    /**
     * Cancel an open apartado and return its pieces to the product's stock.
     */
    public function cancel(Apartado $apartado): RedirectResponse
    {
        $cancelled = DB::transaction(function () use ($apartado): bool {
            $apartado = Apartado::query()->lockForUpdate()->findOrFail($apartado->id_apartados);

            if (! $apartado->isVigente()) {
                return false;
            }

            $apartado->update(['estado' => EstadoApartado::Cancelado]);
            Product::query()->whereKey($apartado->producto_id)->increment('Stock', $apartado->cantidad);

            return true;
        });

        Inertia::flash('toast', $cancelled
            ? ['type' => 'success', 'message' => 'Apartado cancelado. Las piezas volvieron al stock.']
            : ['type' => 'error', 'message' => 'Solo se pueden cancelar apartados activos o en riesgo.']);

        return to_route('apartados.show', $apartado);
    }

    /**
     * Get the paid and pending balances and fines of an apartado loaded with its payment sums.
     *
     * @return array{saldo_pagado: string, saldo_pendiente: string, multas: string}
     */
    private function saldos(Apartado $apartado): array
    {
        $totalCents = Apartado::toCents($apartado->total_compra);
        $pagadoCents = Apartado::toCents($apartado->anticipo) + Apartado::toCents($apartado->abonos_sum_monto ?? 0);

        return [
            'saldo_pagado' => number_format($pagadoCents / 100, 2, '.', ''),
            'saldo_pendiente' => number_format(($totalCents - $pagadoCents) / 100, 2, '.', ''),
            'multas' => number_format(Apartado::toCents($apartado->abonos_sum_multa ?? 0) / 100, 2, '.', ''),
        ];
    }
}

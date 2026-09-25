<?php

namespace App\Http\Controllers;

use App\EstadoApartado;
use App\Http\Requests\StoreApartadoRequest;
use App\Models\Apartado;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApartadoController extends Controller
{
    /**
     * Show the list of apartados, optionally filtered by client or status.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::enum(EstadoApartado::class)],
        ]);

        $apartados = Apartado::query()
            ->with('producto:idProduct,Name_Product')
            ->withSum('abonos', 'monto')
            ->when($filters['buscar'] ?? null, fn (Builder $query, string $buscar) => $query->where('nombre_cliente', 'like', "%{$buscar}%"))
            ->when($filters['estado'] ?? null, fn (Builder $query, string $estado) => $query->where('estado', $estado))
            ->latest('fecha_apartada')
            ->latest('id_apartados')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Apartado $apartado) => [
                ...$apartado->toArray(),
                'saldo_pendiente' => $this->saldoPendiente($apartado),
            ]);

        return Inertia::render('apartados/index', [
            'apartados' => $apartados,
            'filters' => [
                'buscar' => $filters['buscar'] ?? '',
                'estado' => $filters['estado'] ?? '',
            ],
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

        return Inertia::render('apartados/show', [
            'apartado' => [
                ...$apartado->toArray(),
                'saldo_pendiente' => number_format($apartado->saldoPendienteCents() / 100, 2, '.', ''),
                'vencido_por_fecha' => $apartado->isActivo() && $apartado->isPastDeadline(),
            ],
        ]);
    }

    /**
     * Cancel an active apartado and return its pieces to the product's stock.
     */
    public function cancel(Apartado $apartado): RedirectResponse
    {
        $cancelled = DB::transaction(function () use ($apartado): bool {
            $apartado = Apartado::query()->lockForUpdate()->findOrFail($apartado->id_apartados);

            if (! $apartado->isActivo()) {
                return false;
            }

            $apartado->update(['estado' => EstadoApartado::Cancelado]);
            Product::query()->whereKey($apartado->producto_id)->increment('Stock', $apartado->cantidad);

            return true;
        });

        Inertia::flash('toast', $cancelled
            ? ['type' => 'success', 'message' => 'Apartado cancelado. Las piezas volvieron al stock.']
            : ['type' => 'error', 'message' => 'Solo se pueden cancelar apartados activos.']);

        return to_route('apartados.show', $apartado);
    }

    /**
     * Get the pending balance of an apartado loaded with its payments sum.
     */
    private function saldoPendiente(Apartado $apartado): string
    {
        $saldoCents = Apartado::toCents($apartado->total_compra)
            - Apartado::toCents($apartado->anticipo)
            - Apartado::toCents($apartado->abonos_sum_monto ?? 0);

        return number_format($saldoCents / 100, 2, '.', '');
    }
}

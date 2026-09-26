<?php

namespace App\Models\Builders;

use App\EstadoApartado;
use App\Models\Apartado;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Apartado>
 */
class ApartadoBuilder extends Builder
{
    /**
     * The sort options accepted by the apartados list.
     *
     * @var list<string>
     */
    public const array ORDENES = ['recientes', 'antiguos', 'fecha_limite', 'cliente'];

    /**
     * Apply every filter present in the given array.
     *
     * @param  array{buscar?: ?string, estado?: ?string, producto_id?: ?int, desde?: ?string, hasta?: ?string, orden?: ?string}  $filters
     */
    public function filter(array $filters): static
    {
        return $this
            ->buscar($filters['buscar'] ?? null)
            ->estado(isset($filters['estado']) ? EstadoApartado::from($filters['estado']) : null)
            ->deProducto($filters['producto_id'] ?? null)
            ->apartadoDesde($filters['desde'] ?? null)
            ->apartadoHasta($filters['hasta'] ?? null)
            ->ordenar($filters['orden'] ?? null);
    }

    /**
     * Search by the client's name or phone number.
     */
    public function buscar(?string $termino): static
    {
        if (blank($termino)) {
            return $this;
        }

        return $this->where(fn (self $query) => $query
            ->where('nombre_cliente', 'like', "%{$termino}%")
            ->orWhere('telefono_cliente', 'like', "%{$termino}%"));
    }

    /**
     * Only include apartados with the given status.
     */
    public function estado(?EstadoApartado $estado): static
    {
        if ($estado === null) {
            return $this;
        }

        return $this->where('estado', $estado);
    }

    /**
     * Only include apartados of the given product.
     */
    public function deProducto(?int $productoId): static
    {
        if ($productoId === null) {
            return $this;
        }

        return $this->where('producto_id', $productoId);
    }

    /**
     * Only include apartados made on or after the given date.
     */
    public function apartadoDesde(?string $fecha): static
    {
        if (blank($fecha)) {
            return $this;
        }

        return $this->whereDate('fecha_apartada', '>=', $fecha);
    }

    /**
     * Only include apartados made on or before the given date.
     */
    public function apartadoHasta(?string $fecha): static
    {
        if (blank($fecha)) {
            return $this;
        }

        return $this->whereDate('fecha_apartada', '<=', $fecha);
    }

    /**
     * Sort the apartados, newest first by default.
     */
    public function ordenar(?string $orden): static
    {
        return match ($orden) {
            'antiguos' => $this->oldest('fecha_apartada')->oldest('id_apartados'),
            'fecha_limite' => $this->oldest('fecha_limite')->oldest('id_apartados'),
            'cliente' => $this->orderBy('nombre_cliente')->oldest('id_apartados'),
            default => $this->latest('fecha_apartada')->latest('id_apartados'),
        };
    }
}

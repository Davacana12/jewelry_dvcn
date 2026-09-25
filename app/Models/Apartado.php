<?php

namespace App\Models;

use App\EstadoApartado;
use Database\Factories\ApartadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id_apartados
 * @property string $nombre_cliente
 * @property string $telefono_cliente
 * @property int $producto_id
 * @property int $cantidad
 * @property string $total_compra
 * @property string $anticipo
 * @property Carbon $fecha_apartada
 * @property Carbon $fecha_limite
 * @property EstadoApartado $estado
 * @property-read Product $producto
 * @property-read Collection<int, Abono> $abonos
 */
#[Table('Apartados', key: 'id_apartados')]
#[WithoutTimestamps]
#[Fillable(['nombre_cliente', 'telefono_cliente', 'producto_id', 'cantidad', 'total_compra', 'anticipo', 'fecha_apartada', 'fecha_limite', 'estado'])]
class Apartado extends Model
{
    /** @use HasFactory<ApartadoFactory> */
    use HasFactory;

    /**
     * The number of days a client has to pay off an apartado.
     */
    public const int DIAS_PARA_PAGAR = 15;

    /**
     * The minimum down payment, as a percentage of the total.
     */
    public const int PORCENTAJE_ANTICIPO_MINIMO = 20;

    /**
     * Get the product that was put on layaway.
     *
     * @return BelongsTo<Product, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id', 'idProduct');
    }

    /**
     * Convert a money amount to cents to avoid floating point errors.
     */
    public static function toCents(string|int|float $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    /**
     * Get the total in cents of buying the given quantity of a product.
     */
    public static function totalCentsFor(Product $product, int $cantidad): int
    {
        return self::toCents($product->Cost) * $cantidad;
    }

    /**
     * Get the minimum down payment in cents for the given total.
     */
    public static function anticipoMinimoCents(int $totalCents): int
    {
        return (int) ceil($totalCents * self::PORCENTAJE_ANTICIPO_MINIMO / 100);
    }

    /**
     * Get the amount in cents that is still owed on this apartado.
     */
    public function saldoPendienteCents(): int
    {
        $abonadoCents = $this->abonos->sum(fn (Abono $abono): int => self::toCents($abono->monto));

        return self::toCents($this->total_compra) - self::toCents($this->anticipo) - $abonadoCents;
    }

    /**
     * Determine whether the apartado can still receive payments or be cancelled.
     */
    public function isActivo(): bool
    {
        return $this->estado === EstadoApartado::Activo;
    }

    /**
     * Determine whether the payment deadline has passed.
     */
    public function isPastDeadline(): bool
    {
        return $this->fecha_limite->copy()->endOfDay()->isPast();
    }

    /**
     * Get the payments made towards this apartado.
     *
     * @return HasMany<Abono, $this>
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class, 'id_apartado', 'id_apartados');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'total_compra' => 'decimal:2',
            'anticipo' => 'decimal:2',
            'fecha_apartada' => 'datetime',
            'fecha_limite' => 'date',
            'estado' => EstadoApartado::class,
        ];
    }
}

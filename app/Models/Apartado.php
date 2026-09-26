<?php

namespace App\Models;

use App\EstadoApartado;
use App\Models\Builders\ApartadoBuilder;
use Carbon\CarbonImmutable;
use Database\Factories\ApartadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_apartados
 * @property string $nombre_cliente
 * @property string $telefono_cliente
 * @property int $producto_id
 * @property int $cantidad
 * @property string $total_compra
 * @property string $anticipo
 * @property CarbonImmutable $fecha_apartada
 * @property CarbonImmutable $fecha_limite
 * @property EstadoApartado $estado
 * @property-read Product $producto
 * @property-read Collection<int, Abono> $abonos
 *
 * @method static ApartadoBuilder query()
 */
#[Table('Apartados', key: 'id_apartados')]
#[UseEloquentBuilder(ApartadoBuilder::class)]
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
     * Days after the deadline during which payments are still accepted with a fine.
     */
    public const int DIAS_DE_GRACIA = 3;

    /**
     * Fine charged on each late payment, as a percentage of the pending balance.
     */
    public const int PORCENTAJE_MULTA = 2;

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
     * Get the fine in cents for a late payment on the given pending balance.
     */
    public static function multaCentsFor(int $saldoPendienteCents): int
    {
        return (int) round($saldoPendienteCents * self::PORCENTAJE_MULTA / 100);
    }

    /**
     * Determine whether the apartado is still open: it can receive payments or be cancelled.
     */
    public function isVigente(): bool
    {
        return $this->estado->isVigente();
    }

    /**
     * Get the last day late payments are accepted.
     */
    public function fechaFinDeGracia(): CarbonImmutable
    {
        return $this->fecha_limite->addDays(self::DIAS_DE_GRACIA);
    }

    /**
     * Determine whether the deadline has passed but late payments are still accepted.
     */
    public function isInGracePeriod(): bool
    {
        return today()->greaterThan($this->fecha_limite)
            && today()->lessThanOrEqualTo($this->fechaFinDeGracia());
    }

    /**
     * Determine whether the grace period after the deadline is over.
     */
    public function isPastGracePeriod(): bool
    {
        return today()->greaterThan($this->fechaFinDeGracia());
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

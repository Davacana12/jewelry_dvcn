<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AbonoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_abono
 * @property int $id_apartado
 * @property string $monto
 * @property string $multa
 * @property CarbonImmutable $fecha_monto
 * @property-read Apartado $apartado
 */
#[Table('Abonos', key: 'id_abono')]
#[WithoutTimestamps]
#[Fillable(['id_apartado', 'monto', 'multa', 'fecha_monto'])]
class Abono extends Model
{
    /** @use HasFactory<AbonoFactory> */
    use HasFactory;

    /**
     * Get the apartado this payment belongs to.
     *
     * @return BelongsTo<Apartado, $this>
     */
    public function apartado(): BelongsTo
    {
        return $this->belongsTo(Apartado::class, 'id_apartado', 'id_apartados');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'multa' => 'decimal:2',
            'fecha_monto' => 'datetime',
        ];
    }
}

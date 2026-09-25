<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $idProduct
 * @property string $Name_Product
 * @property float $Cost
 * @property int $Stock
 * @property-read Collection<int, Apartado> $apartados
 */
#[Table('Product', key: 'idProduct')]
#[WithoutTimestamps]
#[Fillable(['Name_Product', 'Cost', 'Stock'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the layaways made for this product.
     *
     * @return HasMany<Apartado, $this>
     */
    public function apartados(): HasMany
    {
        return $this->hasMany(Apartado::class, 'producto_id', 'idProduct');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Cost' => 'float',
            'Stock' => 'integer',
        ];
    }
}

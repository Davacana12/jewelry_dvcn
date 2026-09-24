<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $idProduct
 * @property string $Name_Product
 * @property float $Cost
 * @property int $Stock
 */
#[Table('Product', key: 'idProduct')]
#[WithoutTimestamps]
#[Fillable(['Name_Product', 'Cost', 'Stock'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

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

<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait ProductValidationRules
{
    /**
     * Get the validation rules used to validate products.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function productRules(int $minimumStock): array
    {
        return [
            'Name_Product' => ['required', 'string', 'min:5', 'max:255'],
            'Cost' => ['required', 'numeric', 'gt:0'],
            'Stock' => ['required', 'integer', 'min:'.$minimumStock, 'max:4294967295'],
        ];
    }

    /**
     * Get the validation messages used for products.
     *
     * @return array<string, string>
     */
    protected function productMessages(): array
    {
        return [
            'Name_Product.required' => 'El nombre del producto es obligatorio.',
            'Name_Product.string' => 'El nombre del producto debe ser texto.',
            'Name_Product.min' => 'El nombre del producto debe tener al menos :min caracteres.',
            'Name_Product.max' => 'El nombre del producto no puede tener más de :max caracteres.',
            'Cost.required' => 'El costo es obligatorio.',
            'Cost.numeric' => 'El costo debe ser un número.',
            'Cost.gt' => 'El costo debe ser mayor a 0.',
            'Stock.required' => 'El stock es obligatorio.',
            'Stock.integer' => 'El stock debe ser un número entero.',
            'Stock.min' => 'El stock debe ser al menos :min.',
            'Stock.max' => 'El stock no puede ser mayor a :max.',
        ];
    }
}

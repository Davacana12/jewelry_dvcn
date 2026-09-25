<?php

namespace App\Http\Requests;

use App\Models\Apartado;
use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApartadoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_cliente' => ['required', 'string', 'max:255'],
            'telefono_cliente' => ['required', 'string', 'regex:/^\+?[0-9 ]{7,20}$/'],
            'producto_id' => ['required', 'integer', 'exists:Product,idProduct'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'anticipo' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['producto_id', 'cantidad', 'anticipo'])) {
                    return;
                }

                $product = Product::findOrFail($this->integer('producto_id'));
                $cantidad = $this->integer('cantidad');

                if ($cantidad > $product->Stock) {
                    $validator->errors()->add('cantidad', $product->Stock === 0
                        ? 'Este producto está agotado.'
                        : "Solo hay {$product->Stock} piezas disponibles.");

                    return;
                }

                $totalCents = Apartado::totalCentsFor($product, $cantidad);
                $anticipoCents = Apartado::toCents($this->input('anticipo'));
                $minimoCents = Apartado::anticipoMinimoCents($totalCents);

                if ($anticipoCents < $minimoCents) {
                    $validator->errors()->add('anticipo', sprintf(
                        'El anticipo mínimo es de $%s (%d%% del total).',
                        number_format($minimoCents / 100, 2),
                        Apartado::PORCENTAJE_ANTICIPO_MINIMO,
                    ));
                }

                if ($anticipoCents > $totalCents) {
                    $validator->errors()->add('anticipo', sprintf(
                        'El anticipo no puede ser mayor al total de $%s.',
                        number_format($totalCents / 100, 2),
                    ));
                }
            },
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre_cliente.required' => 'El nombre del cliente es obligatorio.',
            'nombre_cliente.max' => 'El nombre del cliente no puede tener más de :max caracteres.',
            'telefono_cliente.required' => 'El teléfono del cliente es obligatorio.',
            'telefono_cliente.regex' => 'El teléfono debe tener entre 7 y 20 dígitos.',
            'producto_id.required' => 'Selecciona un producto.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos :min.',
            'anticipo.required' => 'El anticipo es obligatorio.',
            'anticipo.numeric' => 'El anticipo debe ser un número.',
            'anticipo.gt' => 'El anticipo debe ser mayor a 0.',
        ];
    }
}

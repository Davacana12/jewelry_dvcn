<?php

namespace App\Http\Requests;

use App\Models\Apartado;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAbonoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'gt:0'],
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
                /** @var Apartado $apartado */
                $apartado = $this->route('apartado');

                if (! $apartado->isVigente()) {
                    $validator->errors()->add('monto', 'Solo se pueden registrar abonos en apartados activos o en riesgo.');

                    return;
                }

                if ($apartado->isPastGracePeriod()) {
                    $validator->errors()->add('monto', sprintf(
                        'El plazo de este apartado venció: los %d días de gracia terminaron el %s.',
                        Apartado::DIAS_DE_GRACIA,
                        $apartado->fechaFinDeGracia()->format('d/m/Y'),
                    ));

                    return;
                }

                if ($validator->errors()->has('monto')) {
                    return;
                }

                $saldoCents = $apartado->saldoPendienteCents();

                if (Apartado::toCents($this->input('monto')) > $saldoCents) {
                    $validator->errors()->add('monto', sprintf(
                        'El abono no puede ser mayor al saldo pendiente de $%s.',
                        number_format($saldoCents / 100, 2),
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
            'monto.required' => 'El monto del abono es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.gt' => 'El monto debe ser mayor a 0.',
        ];
    }
}

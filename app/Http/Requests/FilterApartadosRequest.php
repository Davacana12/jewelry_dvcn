<?php

namespace App\Http\Requests;

use App\EstadoApartado;
use App\Models\Builders\ApartadoBuilder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterApartadosRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::enum(EstadoApartado::class)],
            'producto_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'orden' => ['nullable', Rule::in(ApartadoBuilder::ORDENES)],
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
            'estado.enum' => 'El estado seleccionado no es válido.',
            'producto_id.integer' => 'El producto seleccionado no es válido.',
            'desde.date_format' => 'La fecha "desde" no es válida.',
            'hasta.date_format' => 'La fecha "hasta" no es válida.',
            'hasta.after_or_equal' => 'La fecha "hasta" debe ser igual o posterior a la fecha "desde".',
            'orden.in' => 'El orden seleccionado no es válido.',
        ];
    }

    /**
     * Get the validated filters, with every key present and typed.
     *
     * @return array{buscar: ?string, estado: ?string, producto_id: ?int, desde: ?string, hasta: ?string, orden: ?string}
     */
    public function filters(): array
    {
        return [
            'buscar' => $this->validated('buscar'),
            'estado' => $this->validated('estado'),
            'producto_id' => $this->filled('producto_id') ? $this->integer('producto_id') : null,
            'desde' => $this->validated('desde'),
            'hasta' => $this->validated('hasta'),
            'orden' => $this->validated('orden'),
        ];
    }
}

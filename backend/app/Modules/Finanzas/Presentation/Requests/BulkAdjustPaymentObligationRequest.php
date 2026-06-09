<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for bulk payment obligation adjustments.
 */
class BulkAdjustPaymentObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'filters' => ['required', 'array'],
            'filters.obligation_ids' => ['sometimes', 'array', 'min:1'],
            'filters.obligation_ids.*' => ['uuid'],
            'filters.concept_id' => ['sometimes', 'uuid'],
            'filters.grade_id' => ['sometimes', 'uuid'],
            'filters.section_id' => ['sometimes', 'uuid'],
            'adjustment_type' => ['required', 'string', 'in:charge,discount,waiver'],
            'amount' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'filters.required' => 'Los filtros son requeridos.',
            'filters.array' => 'Los filtros deben ser un objeto JSON.',
            'filters.obligation_ids.min' => 'Debe incluir al menos una obligación si especifica IDs.',
            'filters.obligation_ids.*.uuid' => 'Cada ID de obligación debe ser UUID válido.',
            'filters.concept_id.uuid' => 'El ID del concepto debe ser UUID válido.',
            'filters.grade_id.uuid' => 'El ID del grado debe ser UUID válido.',
            'filters.section_id.uuid' => 'El ID de la sección debe ser UUID válido.',
            'adjustment_type.required' => 'El tipo de ajuste es requerido.',
            'adjustment_type.in' => 'El tipo de ajuste debe ser: charge, discount, o waiver.',
            'amount.required' => 'El monto es requerido.',
            'amount.numeric' => 'El monto debe ser numérico.',
            'amount.min' => 'El monto no puede ser negativo.',
            'amount.regex' => 'El monto debe tener máximo 2 decimales.',
            'reason.required' => 'El motivo del ajuste es requerido.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
            'reason.max' => 'El motivo no puede exceder 1000 caracteres.',
        ];
    }
}

<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for listing payment obligations.
 */
class ListPaymentObligationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['sometimes', 'uuid'],
            'concept_id' => ['sometimes', 'uuid'],
            'estado' => ['sometimes', 'string', 'in:pendiente,pagado,vencido'],
            'due_date_from' => ['sometimes', 'date'],
            'due_date_to' => ['sometimes', 'date', 'after_or_equal:due_date_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.uuid' => 'El ID del estudiante debe ser UUID válido.',
            'concept_id.uuid' => 'El ID del concepto debe ser UUID válido.',
            'estado.in' => 'El estado debe ser: pendiente, pagado, o vencido.',
            'due_date_from.date' => 'La fecha de inicio debe ser válida.',
            'due_date_to.date' => 'La fecha de fin debe ser válida.',
            'due_date_to.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'per_page.integer' => 'Items por página debe ser un número entero.',
            'per_page.min' => 'Items por página debe ser al menos 1.',
            'per_page.max' => 'Items por página no puede exceder 100.',
        ];
    }
}

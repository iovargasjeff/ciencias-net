<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for generating payment obligations.
 */
class GeneratePaymentObligationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'uuid', 'exists:periodos_academicos,id'],
            'concept_id' => ['required', 'uuid', 'exists:conceptos_pago,id'],
            'due_date' => ['required', 'date', 'after:today'],
            'student_ids' => ['sometimes', 'array', 'min:1'],
            'student_ids.*' => ['uuid', 'exists:alumnos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_period_id.required' => 'El período académico es requerido.',
            'academic_period_id.uuid' => 'El ID del período debe ser UUID válido.',
            'academic_period_id.exists' => 'El período académico no existe.',
            'concept_id.required' => 'El concepto de pago es requerido.',
            'concept_id.uuid' => 'El ID del concepto debe ser UUID válido.',
            'concept_id.exists' => 'El concepto de pago no existe.',
            'due_date.required' => 'La fecha de vencimiento es requerida.',
            'due_date.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'due_date.after' => 'La fecha de vencimiento debe ser futura.',
            'student_ids.array' => 'Los IDs de estudiantes deben ser un array.',
            'student_ids.min' => 'Debe incluir al menos un estudiante si especifica IDs.',
            'student_ids.*.uuid' => 'Cada ID de estudiante debe ser UUID válido.',
            'student_ids.*.exists' => 'Uno o más estudiantes no existen.',
        ];
    }
}

<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateStudentBenefitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', Rule::exists('alumnos', 'id')],
            'benefit_type' => ['required', Rule::in(['percentage', 'fixed', 'waiver'])],
            'value' => ['nullable', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'concept_ids' => ['nullable', 'array'],
            'concept_ids.*' => ['uuid', 'distinct', Rule::exists('conceptos_pago', 'id')],
            'stackable_with_early_payment' => ['sometimes', 'boolean'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('benefit_type');
            $value = $this->input('value');

            if ($type === 'waiver' && $value !== null) {
                $validator->errors()->add('value', 'La exoneración no debe enviar valor.');
            }

            if ($type === 'percentage' && ((float) $value <= 0 || (float) $value > 100)) {
                $validator->errors()->add('value', 'El porcentaje debe ser mayor que 0 y menor o igual a 100.');
            }

            if ($type === 'fixed' && (float) $value <= 0) {
                $validator->errors()->add('value', 'El monto fijo debe ser mayor que 0.');
            }
        });
    }
}

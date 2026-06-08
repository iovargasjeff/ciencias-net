<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreatePaymentConceptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'academic_period_id' => ['nullable', 'uuid', Rule::exists('periodos_academicos', 'id')],
            'type' => ['required', Rule::in(['cuota_ingreso', 'matricula', 'mensualidad', 'otro'])],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'recurrence' => ['nullable', Rule::in(['single', 'monthly', 'annual'])],
            'early_payment_discount' => ['nullable', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'early_payment_deadline' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') === 'mensualidad' && ! $this->filled('month')) {
                $validator->errors()->add('month', 'El mes es obligatorio para mensualidades.');
            }

            if ($this->filled('early_payment_discount') && (float) $this->input('early_payment_discount') > (float) $this->input('amount')) {
                $validator->errors()->add('early_payment_discount', 'El descuento por pronto pago no puede superar el monto.');
            }
        });
    }
}

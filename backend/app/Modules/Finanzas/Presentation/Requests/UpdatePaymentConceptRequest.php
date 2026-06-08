<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaymentConceptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'amount' => ['sometimes', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'type' => ['sometimes', Rule::in(['cuota_ingreso', 'matricula', 'mensualidad', 'otro'])],
            'year' => ['sometimes', 'integer', 'between:2000,2100'],
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
            $amount = (float) $this->input('amount', PHP_FLOAT_MAX);
            if ($this->filled('early_payment_discount') && (float) $this->input('early_payment_discount') > $amount) {
                $validator->errors()->add('early_payment_discount', 'El descuento por pronto pago no puede superar el monto.');
            }
        });
    }
}

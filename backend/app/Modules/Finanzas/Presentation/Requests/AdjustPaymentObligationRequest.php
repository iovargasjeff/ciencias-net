<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for adjusting a pending payment obligation.
 */
class AdjustPaymentObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'adjustment_type' => ['required', 'string', 'in:charge,discount,waiver'],
            'amount' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_type.required' => 'El tipo de ajuste es requerido.',
            'adjustment_type.in' => 'El tipo de ajuste debe ser: charge (cobro), discount (descuento), waiver (condonación).',
            'amount.required' => 'El monto es requerido.',
            'amount.numeric' => 'El monto debe ser numérico.',
            'amount.min' => 'El monto no puede ser negativo.',
            'amount.regex' => 'El monto debe tener máximo 2 decimales (ej: 100.50).',
            'reason.required' => 'El motivo del ajuste es requerido.',
            'reason.min' => 'El motivo debe tener al menos 1 carácter.',
            'reason.max' => 'El motivo no puede exceder 1000 caracteres.',
        ];
    }
}

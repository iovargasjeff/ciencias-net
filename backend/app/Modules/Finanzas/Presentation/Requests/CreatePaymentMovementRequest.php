<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreatePaymentMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'obligation_id' => ['required', 'uuid', 'exists:obligaciones_pago,id'],
            'movement_type' => ['required', 'string', 'in:payment,reversal,refund'],
            'amount' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'occurred_at' => ['required', 'date_format:Y-m-d\TH:i:s'],
            'method' => [
                'required_if:movement_type,payment',
                'prohibited_if:movement_type,reversal,refund',
                'string',
                'in:cash,transfer,card,yape,plin,other',
            ],
            'reference' => [
                'nullable',
                'string',
                'min:1',
                'max:150',
                'prohibited_if:movement_type,reversal,refund',
            ],
            'reason' => [
                'nullable',
                'string',
                'min:1',
                'max:1000',
                'prohibited_if:movement_type,payment',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();
            $movementType = $data['movement_type'] ?? null;
            $method = $data['method'] ?? null;
            $reference = $data['reference'] ?? null;
            $reason = $data['reason'] ?? null;

            if ($movementType === 'payment' && $method !== 'cash' && empty($reference)) {
                $validator->errors()->add(
                    'reference',
                    'La referencia es requerida para transferencias, Yape, Plin o tarjeta.'
                );
            }

            if (in_array($movementType, ['reversal', 'refund'], true) && empty($reason)) {
                $validator->errors()->add(
                    'reason',
                    'El motivo es requerido para anulaciones y devoluciones.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'obligation_id.required' => 'La obligación es requerida.',
            'obligation_id.uuid' => 'El ID de la obligación debe ser UUID válido.',
            'obligation_id.exists' => 'La obligación no existe.',
            'movement_type.required' => 'El tipo de movimiento es requerido.',
            'movement_type.in' => 'El tipo de movimiento debe ser: payment, reversal o refund.',
            'amount.required' => 'El monto es requerido.',
            'amount.regex' => 'El monto debe ser un número decimal válido.',
            'occurred_at.required' => 'La fecha del movimiento es requerida.',
            'occurred_at.date_format' => 'La fecha debe tener formato ISO 8601 (Y-m-d\TH:i:s).',
            'method.required_if' => 'El método de pago es requerido para pagos.',
            'method.in' => 'El método debe ser: cash, transfer, yape, plin, card u other.',
        ];
    }
}

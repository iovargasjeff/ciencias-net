<?php

namespace App\Http\Requests\IdentityAccess;

use Illuminate\Foundation\Http\FormRequest;

class ActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
            'reason' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'active' => [
                'description' => 'Estado activo de la cuenta.',
                'example' => true,
            ],
            'reason' => [
                'description' => 'Motivo administrativo del cambio.',
                'example' => 'Revisión administrativa completada.',
            ],
        ];
    }
}

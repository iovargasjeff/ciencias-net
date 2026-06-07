<?php

namespace App\Http\Requests\Biometrics;

use Illuminate\Foundation\Http\FormRequest;

class RevokeBiometricConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_dispositivos') === true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}

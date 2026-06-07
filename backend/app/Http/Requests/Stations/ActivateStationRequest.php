<?php

namespace App\Http\Requests\Stations;

use Illuminate\Foundation\Http\FormRequest;

class ActivateStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activation_code' => ['required', 'string', 'max:191'],
            'device_name' => ['required', 'string', 'max:150'],
        ];
    }
}

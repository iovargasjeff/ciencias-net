<?php

namespace App\Http\Requests\Stations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_dispositivos') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'mode' => ['required', 'string', Rule::in(['entry', 'exit', 'mixed'])],
        ];
    }
}

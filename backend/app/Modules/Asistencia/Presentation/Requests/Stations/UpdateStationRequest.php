<?php

namespace App\Modules\Asistencia\Presentation\Requests\Stations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_dispositivos') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'location' => ['sometimes', 'string', 'max:255'],
            'mode' => ['sometimes', 'string', Rule::in(['entry', 'exit', 'mixed'])],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}

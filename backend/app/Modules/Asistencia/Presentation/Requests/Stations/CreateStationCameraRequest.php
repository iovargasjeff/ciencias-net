<?php

namespace App\Modules\Asistencia\Presentation\Requests\Stations;

use Illuminate\Foundation\Http\FormRequest;

class CreateStationCameraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_dispositivos') === true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'device_identifier' => ['required', 'string', 'max:191'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}

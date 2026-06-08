<?php

namespace App\Modules\Asistencia\Presentation\Requests\Stations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitStationCaptureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('station');
    }

    public function rules(): array
    {
        $station = $this->attributes->get('station');

        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'camera_id' => ['required', 'uuid', Rule::exists('camaras_estacion', 'id')->where('estacion_id', $station?->id)],
            'captured_at' => ['required', 'date'],
        ];
    }
}

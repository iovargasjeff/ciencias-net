<?php

namespace App\Modules\Asistencia\Presentation\Requests\Stations;

use Illuminate\Foundation\Http\FormRequest;

class ReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_dispositivos') === true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000']];
    }
}

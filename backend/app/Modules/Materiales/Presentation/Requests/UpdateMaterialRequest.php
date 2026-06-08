<?php

namespace App\Modules\Materiales\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'semana' => ['nullable', 'integer', 'min:1', 'max:52'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}

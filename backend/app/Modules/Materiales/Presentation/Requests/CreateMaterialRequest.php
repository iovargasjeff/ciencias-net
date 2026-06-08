<?php

namespace App\Modules\Materiales\Presentation\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateMaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carga_academica_id' => ['required', 'uuid', 'exists:carga_academica,id'],
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'semana' => ['nullable', 'integer', 'min:1', 'max:52'],
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB in KB
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar,mp4',
            ],
        ];
    }
}

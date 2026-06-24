<?php

namespace App\Modules\Shared\Presentation\Requests;

use App\Modules\Shared\Infrastructure\Models\PrivateFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UploadPrivateFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PrivateFile::class) === true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'])
                    ->max('50mb'),
            ],
            'purpose' => ['required', Rule::in(['material', 'incident_evidence', 'psychology', 'biometric_exception', 'report', 'other'])],
            'checksum_sha256' => ['sometimes', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'metadata' => ['sometimes', 'array'],
            'metadata.*' => ['string', 'max:200'],
        ];
    }
}

<?php

namespace App\Modules\Finanzas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendPaymentRemindersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar_finanzas') === true;
    }

    public function rules(): array
    {
        return [
            'obligation_ids' => ['required', 'array', 'min:1'],
            'obligation_ids.*' => ['required', 'string', 'uuid'],
            'channel' => ['required', 'string', 'in:email,in_app,both'],
        ];
    }
}

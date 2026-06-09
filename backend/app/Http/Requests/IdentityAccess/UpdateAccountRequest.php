<?php

namespace App\Http\Requests\IdentityAccess;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', User::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($this->route('accountId'))],
        ];
    }
}

<?php

namespace App\Http\Requests\IdentityAccess;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', User::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'dni' => ['nullable', 'string', 'size:8'],
            'last_names' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:15'],
            'notification_email' => ['nullable', 'email:rfc', 'max:191'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $roles = $this->array('roles');

                if (in_array('superadmin', $roles, true)) {
                    $validator->errors()->add('roles', 'El rol superadmin no puede crearse desde la API ordinaria.');
                }

                $requiresProfile = array_intersect($roles, ['docente', 'padre', 'alumno']);
                if ($requiresProfile !== []) {
                    foreach (['dni', 'last_names'] as $field) {
                        if (! $this->filled($field)) {
                            $validator->errors()->add($field, 'Este campo es obligatorio para el rol seleccionado.');
                        }
                    }
                }

                if (in_array('docente', $roles, true) && ! $this->filled('phone')) {
                    $validator->errors()->add('phone', 'Este campo es obligatorio para docentes.');
                }

                if (in_array('padre', $roles, true)) {
                    foreach (['phone', 'notification_email'] as $field) {
                        if (! $this->filled($field)) {
                            $validator->errors()->add($field, 'Este campo es obligatorio para familiares.');
                        }
                    }
                }

                if ($this->filled('dni')) {
                    $dni = $this->string('dni')->toString();
                    $tables = [
                        'docente' => 'docentes',
                        'padre' => 'padres',
                        'alumno' => 'alumnos',
                    ];
                    foreach ($tables as $role => $table) {
                        if (in_array($role, $roles, true) && DB::table($table)->where('dni', $dni)->exists()) {
                            $validator->errors()->add('dni', 'El DNI ya está registrado para el rol seleccionado.');
                        }
                    }
                }
            },
        ];
    }
}

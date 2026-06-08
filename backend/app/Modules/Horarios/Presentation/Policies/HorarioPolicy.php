<?php

namespace App\Modules\Horarios\Presentation\Policies;

use App\Modules\Horarios\Infrastructure\Models\Horario;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HorarioPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Horario $horario): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['superadmin', 'coordinador_academico']);
    }

    public function update(User $user, Horario $horario): bool
    {
        return $user->hasRole(['superadmin', 'coordinador_academico']);
    }

    public function delete(User $user, Horario $horario): bool
    {
        return $user->hasRole(['superadmin', 'coordinador_academico']);
    }
}

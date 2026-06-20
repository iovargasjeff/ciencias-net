<?php

namespace App\Modules\Usuarios\Presentation\Policies;

use App\Modules\Usuarios\Infrastructure\Models\User;

class UserPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->hasRole('superadmin') || $actor->can('gestionar_usuarios');
    }

    public function lookupAcademic(User $actor): bool
    {
        return $this->manage($actor) || $actor->hasRole('coordinador_academico');
    }

    public function changeSensitiveState(User $actor, User $target): bool
    {
        return $this->manage($actor) && $actor->isNot($target);
    }

    public function assignRoles(User $actor, User $target, array $roles): bool
    {
        if (! $this->changeSensitiveState($actor, $target)) {
            return false;
        }

        return $actor->hasRole('superadmin')
            || count(array_intersect($roles, ['superadmin', 'gestor_usuarios'])) === 0;
    }
}

<?php

namespace App\Modules\Comunicados\Presentation\Policies;

use App\Modules\Comunicados\Infrastructure\Models\Comunicado;
use App\Modules\Usuarios\Infrastructure\Models\User;

class ComunicadoPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'toe', 'coordinador_academico']);
    }

    public function archive(User $user, Comunicado $comunicado): bool
    {
        return $user->hasAnyRole(['superadmin', 'toe', 'coordinador_academico']);
    }
}

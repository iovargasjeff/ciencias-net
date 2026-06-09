<?php

namespace App\Modules\Academico\Presentation\Policies;

use App\Modules\Usuarios\Infrastructure\Models\User;

class PeriodoAcademicoPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->roles()->exists();
    }

    public function manage(User $actor): bool
    {
        return $actor->hasAnyRole(['superadmin', 'coordinador_academico']);
    }
}

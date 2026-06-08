<?php

namespace App\Modules\Academico\Presentation\Policies;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamenPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'coordinador_academico', 'docente', 'alumno', 'padre']);
    }

    public function create(User $user, string $cargaAcademicaId): bool
    {
        return $user->hasAnyRole(['superadmin', 'coordinador_academico']);
    }
}

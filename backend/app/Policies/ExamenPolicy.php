<?php

namespace App\Policies;

use App\Models\CargaAcademica;
use App\Models\User;
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
        if ($user->hasAnyRole(['superadmin', 'coordinador_academico'])) {
            return true;
        }

        if ($user->hasRole('docente')) {
            $carga = CargaAcademica::with('docente')->find($cargaAcademicaId);
            return $carga && $carga->docente && $carga->docente->user_id === $user->id;
        }

        return false;
    }
}

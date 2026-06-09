<?php

namespace App\Modules\Academico\Presentation\Policies;

use App\Modules\Usuarios\Infrastructure\Models\User;

class AcademicReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'coordinador_academico', 'docente', 'alumno', 'padre']);
    }

    public function generate(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'coordinador_academico', 'docente', 'alumno', 'padre']);
    }
}

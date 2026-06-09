<?php

namespace App\Modules\Incidencias\Presentation\Policies;

use App\Modules\Incidencias\Infrastructure\Models\Incidencia;
use App\Modules\Usuarios\Infrastructure\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'auxiliar', 'toe', 'psicologia']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'auxiliar', 'toe']);
    }

    public function transition(User $user, Incidencia $incidencia): bool
    {
        return $user->hasAnyRole(['superadmin', 'auxiliar', 'toe']);
    }

    public function addFollowUp(User $user, Incidencia $incidencia): bool
    {
        // Solo TOE y superadmin pueden agregar seguimientos según diseño (o auxiliares dependiendo de la regla exacta,
        // pero en api-contracts.yaml dice roles: superadmin, toe)
        return $user->hasAnyRole(['superadmin', 'toe']);
    }

    public function generateReport(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'auxiliar', 'toe']);
    }
}

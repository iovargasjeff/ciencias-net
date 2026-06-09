<?php

namespace App\Modules\Psicologia\Presentation\Policies;

use App\Modules\Usuarios\Infrastructure\Models\User;

class PsychologyCarePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'psicologia']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'psicologia']);
    }
}

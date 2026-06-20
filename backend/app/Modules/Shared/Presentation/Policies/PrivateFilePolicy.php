<?php

namespace App\Modules\Shared\Presentation\Policies;

use App\Modules\Shared\Infrastructure\Models\PrivateFile;
use App\Modules\Usuarios\Infrastructure\Models\User;

class PrivateFilePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'coordinador_academico', 'docente', 'toe', 'auxiliar', 'psicologia', 'administrativo']);
    }

    public function view(User $user, PrivateFile $privateFile): bool
    {
        if ($privateFile->isDeleted() || $privateFile->isExpired()) {
            return false;
        }

        if ($privateFile->uploaded_by === $user->id || $user->hasRole('superadmin')) {
            return true;
        }

        return match ($privateFile->purpose) {
            'material' => $user->hasAnyRole(['coordinador_academico', 'docente']),
            'incident_evidence' => $user->hasAnyRole(['toe', 'auxiliar']),
            'psychology' => $user->hasRole('psicologia'),
            'biometric_exception' => $user->hasAnyRole(['auxiliar', 'toe']),
            'report' => $user->hasAnyRole(['coordinador_academico', 'administrativo']),
            default => false,
        };
    }
}

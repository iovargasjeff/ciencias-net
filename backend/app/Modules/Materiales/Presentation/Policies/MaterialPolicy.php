<?php

namespace App\Modules\Materiales\Presentation\Policies;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Materiales\Infrastructure\Models\Material;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaterialPolicy
{
    public function viewAny(User $user, CargaAcademica $carga): bool
    {
        if ($user->hasRole('superadmin') || $user->hasRole('coordinador_academico') || $user->hasRole('director')) {
            return true;
        }

        if ($user->hasRole('docente')) {
            $docenteId = DB::table('docentes')->where('user_id', $user->id)->value('id');

            return DB::table('carga_academica')
                ->where('id', $carga->id)
                ->where('docente_id', $docenteId)
                ->exists();
        }

        if ($user->hasRole('alumno')) {
            $alumnoId = DB::table('alumnos')->where('user_id', $user->id)->value('id');

            return DB::table('matriculas')
                ->where('seccion_id', $carga->seccion_id)
                ->where('alumno_id', $alumnoId)
                ->where('estado', 'activa')
                ->exists();
        }

        if ($user->hasRole('padre')) {
            $padreId = DB::table('padres')->where('user_id', $user->id)->value('id');
            $studentIds = DB::table('familia_alumno')->where('padre_id', $padreId)->pluck('alumno_id');

            return DB::table('matriculas')
                ->where('seccion_id', $carga->seccion_id)
                ->whereIn('alumno_id', $studentIds)
                ->where('estado', 'activa')
                ->exists();
        }

        return false;
    }

    public function view(User $user, Material $material): bool
    {
        return $this->viewAny($user, $material->cargaAcademica);
    }

    public function create(User $user, ?CargaAcademica $carga = null): bool
    {
        Log::info('MaterialPolicy@create', [
            'user' => $user->id,
            'carga_academica_id' => $carga ? $carga->id : null,
        ]);

        if (! $carga) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('coordinador_academico')) {
            return true;
        }

        if ($user->hasRole('docente')) {
            $docenteId = DB::table('docentes')->where('user_id', $user->id)->value('id');

            return DB::table('carga_academica')
                ->where('id', $carga->id)
                ->where('docente_id', $docenteId)
                ->exists();
        }

        return false;
    }

    public function update(User $user, Material $material): bool
    {
        return $this->create($user, $material->cargaAcademica);
    }

    public function delete(User $user, Material $material): bool
    {
        return $this->create($user, $material->cargaAcademica);
    }
}

<?php

namespace App\Modules\Usuarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DniSearchController extends Controller
{
    public function searchStudents(Request $request): JsonResponse
    {
        Gate::authorize('manage', \App\Modules\Usuarios\Infrastructure\Models\User::class);
        $request->validate(['dni' => 'required|string']);

        $alumno = Alumno::where('dni', $request->query('dni'))->first();
        if (!$alumno) {
            return response()->json(['data' => null], 404);
        }

        return response()->json([
            'data' => [
                'id' => $alumno->id,
                'user_id' => $alumno->user_id,
                'dni' => $alumno->dni,
                'name' => trim($alumno->nombres . ' ' . $alumno->apellidos),
            ]
        ]);
    }

    public function searchParents(Request $request): JsonResponse
    {
        Gate::authorize('manage', \App\Modules\Usuarios\Infrastructure\Models\User::class);
        $request->validate(['dni' => 'required|string']);

        $padre = Padre::where('dni', $request->query('dni'))->first();
        if (!$padre) {
            return response()->json(['data' => null], 404);
        }

        return response()->json([
            'data' => [
                'id' => $padre->id,
                'user_id' => $padre->user_id,
                'dni' => $padre->dni,
                'name' => trim($padre->nombres . ' ' . $padre->apellidos),
            ]
        ]);
    }

    public function searchTeachers(Request $request): JsonResponse
    {
        Gate::authorize('manage', \App\Modules\Usuarios\Infrastructure\Models\User::class);
        $request->validate(['dni' => 'required|string']);

        $docente = Docente::where('dni', $request->query('dni'))->first();
        if (!$docente) {
            return response()->json(['data' => null], 404);
        }

        return response()->json([
            'data' => [
                'id' => $docente->id,
                'user_id' => $docente->user_id,
                'dni' => $docente->dni,
                'name' => trim($docente->nombres . ' ' . $docente->apellidos),
            ]
        ]);
    }
}

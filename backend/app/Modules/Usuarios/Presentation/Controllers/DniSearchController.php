<?php

namespace App\Modules\Usuarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DniSearchController extends Controller
{
    public function searchStudents(Request $request): JsonResponse
    {
        Gate::authorize('lookupAcademic', User::class);
        $request->validate([
            'dni' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'min:3'],
        ]);

        $query = Alumno::query()->with('user');
        $query->when($request->filled('dni'), fn ($q) => $q->where('dni', $request->query('dni')));
        $query->when($request->filled('search'), function ($q) use ($request): void {
            $term = '%'.trim($request->string('search')->toString()).'%';
            $q->where(function ($inner) use ($term): void {
                $inner->where('dni', 'like', $term)
                    ->orWhere('nombres', 'like', $term)
                    ->orWhere('apellidos', 'like', $term)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $term));
            });
        });

        $students = $query->orderBy('apellidos')->limit(20)->get();
        if ($request->filled('dni') && $students->isEmpty()) {
            return response()->json(['data' => null], 404);
        }

        return response()->json(['data' => $students->map(fn (Alumno $alumno) => [
            'id' => $alumno->id,
            'user_id' => $alumno->user_id,
            'dni' => $alumno->dni,
            'name' => trim($alumno->nombres.' '.$alumno->apellidos),
        ])->values()]);
    }

    public function searchParents(Request $request): JsonResponse
    {
        Gate::authorize('manage', User::class);
        $request->validate(['dni' => 'required|string']);

        $padre = Padre::where('dni', $request->query('dni'))->first();
        if (! $padre) {
            return response()->json(['data' => null], 404);
        }

        return response()->json([
            'data' => [
                'id' => $padre->id,
                'user_id' => $padre->user_id,
                'dni' => $padre->dni,
                'name' => trim($padre->nombres.' '.$padre->apellidos),
            ],
        ]);
    }

    public function searchTeachers(Request $request): JsonResponse
    {
        Gate::authorize('lookupAcademic', User::class);
        $request->validate(['dni' => 'sometimes|string', 'search' => 'sometimes|string|max:150']);

        $query = Docente::query();
        $query->when($request->filled('dni'), fn ($q) => $q->where('dni', $request->query('dni')));
        $query->when($request->filled('search'), function ($q) use ($request): void {
            $term = '%'.$request->query('search').'%';
            $q->where(fn ($inner) => $inner->where('nombres', 'like', $term)->orWhere('apellidos', 'like', $term)->orWhere('dni', 'like', $term));
        });

        $teachers = $query->limit(20)->get();
        if ($request->filled('dni') && $teachers->isEmpty()) {
            return response()->json(['data' => null], 404);
        }

        return response()->json(['data' => $teachers->map(fn (Docente $docente) => [
            'id' => $docente->id,
            'user_id' => $docente->user_id,
            'dni' => $docente->dni,
            'name' => trim($docente->nombres.' '.$docente->apellidos),
        ])->values()]);
    }
}

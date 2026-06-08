<?php

namespace App\Modules\Usuarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\CreateFamilyLinkRequest;
use App\Http\Resources\FamilyLinkResource;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class FamilyLinkController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manageFamilyLinks', Alumno::class);
        $query = $this->linksQuery()->when($request->filled('student_id'), fn ($q) => $q->where('ap.alumno_id', $request->string('student_id')));

        return FamilyLinkResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(CreateFamilyLinkRequest $request, AuditLogger $audit): JsonResponse
    {
        $parent = Padre::where('user_id', $request->string('parent_account_id'))->firstOrFail();
        $duplicateExists = DB::table('alumno_padre')
            ->where('alumno_id', $request->string('student_id'))
            ->where('padre_id', $parent->id)
            ->exists();

        if ($duplicateExists) {
            return response()->json([
                'error' => [
                    'code' => 'conflict',
                    'message' => 'The requested relationship already exists.',
                ],
            ], 409);
        }

        $id = (string) Str::uuid();
        DB::table('alumno_padre')->insert([
            'id' => $id, 'alumno_id' => $request->string('student_id'), 'padre_id' => $parent->id,
            'relacion' => $request->string('relationship'), 'es_contacto_principal' => false, 'recibe_notificaciones' => true,
        ]);
        $audit->record($request, 'family_link.created', $request->user(), subject: $id, newValues: $request->validated(), subjectType: 'family_link');
        $link = $this->linksQuery()->where('ap.id', $id)->firstOrFail();

        return response()->json(['data' => new FamilyLinkResource($link)], 201);
    }

    public function destroy(Request $request, string $familyLinkId, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('manageFamilyLinks', Alumno::class);
        $link = $this->linksQuery()->where('ap.id', $familyLinkId)->firstOrFail();
        DB::table('alumno_padre')->where('id', $familyLinkId)->delete();
        $audit->record($request, 'family_link.removed', $request->user(), subject: $familyLinkId, oldValues: (array) $link, subjectType: 'family_link');

        return response()->json(null, 204);
    }

    public function linkedStudents(Request $request): JsonResponse
    {
        $parent = $request->user()->padre;
        abort_unless($parent, 403);
        $students = $parent->alumnos()->get()->map(fn (Alumno $student) => [
            'id' => $student->id, 'name' => trim($student->nombres.' '.$student->apellidos),
            'relationship' => $student->pivot->relacion,
        ]);

        return response()->json(['data' => $students]);
    }

    public function summary(Request $request, string $studentId): JsonResponse
    {
        $student = Alumno::findOrFail($studentId);
        Gate::authorize('viewLinked', $student);
        $student->load('matriculas.seccion.grado.periodoAcademico');

        return response()->json(['data' => [
            'id' => $student->id, 'name' => trim($student->nombres.' '.$student->apellidos),
            'enrollments' => $student->matriculas->map(fn ($enrollment) => [
                'id' => $enrollment->id, 'section' => $enrollment->seccion->nombre,
                'grade' => $enrollment->seccion->grado->nombre,
                'academic_period' => $enrollment->seccion->grado->periodoAcademico->nombre,
            ]),
            'biometric_status' => 'not_enrolled',
        ]]);
    }

    private function linksQuery()
    {
        return DB::table('alumno_padre as ap')
            ->join('alumnos as a', 'a.id', '=', 'ap.alumno_id')
            ->join('padres as p', 'p.id', '=', 'ap.padre_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->selectRaw("ap.id, ap.alumno_id, ap.padre_id, p.user_id as parent_account_id, ap.relacion, a.nombres || ' ' || a.apellidos as student_name, p.nombres || ' ' || p.apellidos as parent_name");
    }
}

<?php

namespace App\Modules\Academico\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicEntityRequest;
use App\Http\Resources\AcademicResource;
use App\Modules\Academico\Domain\GradeCatalog;
use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicController extends Controller
{
    public function periods(Request $request)
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);

        return AcademicResource::collection(PeriodoAcademico::query()->with('bimestres')->latest('fecha_inicio')->paginate($this->perPage($request)));
    }

    public function storePeriod(AcademicEntityRequest $request, AuditLogger $audit): JsonResponse
    {
        $terms = $request->input('terms', []);
        $this->validateTerms($terms);
        $period = DB::transaction(function () use ($request, $terms) {
            $period = PeriodoAcademico::create([
                'nombre' => $request->string('name'), 'tipo' => 'school_year',
                'fecha_inicio' => $request->date('start_date'), 'fecha_fin' => $request->date('end_date'),
                'estado' => $this->statusToDatabase($request->string('status', 'draft')), 'creado_por' => $request->user()->id,
            ]);

            foreach ($terms as $term) {
                $period->bimestres()->create([
                    'nombre' => $term['name'],
                    'fecha_inicio' => $term['start_date'],
                    'fecha_fin' => $term['end_date'],
                ]);
            }

            return $period->load('bimestres');
        });
        $audit->record($request, 'academic_period.created', $request->user(), $period);

        return $this->created($period);
    }

    public function showPeriod(string $academicPeriodId): JsonResponse
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);

        return $this->resource(PeriodoAcademico::with('bimestres')->findOrFail($academicPeriodId));
    }

    public function updatePeriod(AcademicEntityRequest $request, string $academicPeriodId, AuditLogger $audit): JsonResponse
    {
        $academicPeriod = PeriodoAcademico::findOrFail($academicPeriodId);
        $old = $academicPeriod->toArray();
        $data = [];
        if ($request->has('name')) {
            $data['nombre'] = $request->string('name');
        }
        if ($request->has('start_date')) {
            $data['fecha_inicio'] = $request->date('start_date');
        }
        if ($request->has('end_date')) {
            $data['fecha_fin'] = $request->date('end_date');
        }
        if ($request->has('status')) {
            $data['estado'] = $this->statusToDatabase($request->string('status'));
        }
        DB::transaction(fn () => $academicPeriod->update($data));
        $audit->record($request, 'academic_period.updated', $request->user(), $academicPeriod, $old, $academicPeriod->toArray());

        return $this->resource($academicPeriod);
    }

    public function grades(Request $request)
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);
        $query = Grado::query()->latest();
        $query->when($request->filled('academic_period_id'), fn ($q) => $q->where('periodo_academico_id', $request->string('academic_period_id')));

        return AcademicResource::collection($query->paginate($this->perPage($request)));
    }

    public function gradeCatalog(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);

        return response()->json(['data' => GradeCatalog::all()]);
    }

    public function storeGrade(AcademicEntityRequest $request): JsonResponse
    {
        $periodId = $request->input('academic_period_id') ?? PeriodoAcademico::query()->latest('fecha_inicio')->value('id');
        throw_if(! $periodId, ValidationException::withMessages(['academic_period_id' => ['Debe existir un periodo académico.']]));

        $catalog = GradeCatalog::findByCodeOrName($request->input('catalog_code') ?? $request->input('name'));
        if (! $catalog) {
            throw ValidationException::withMessages(['catalog_code' => ['El grado debe pertenecer al catálogo académico aprobado.']]);
        }

        return $this->created(Grado::create([
            'periodo_academico_id' => $periodId,
            'catalog_code' => $catalog['code'],
            'nombre' => $catalog['name'],
            'nivel' => $catalog['level'],
            'orden' => $catalog['order'],
            'activo' => true,
        ]));
    }

    public function updateGrade(AcademicEntityRequest $request, string $id): JsonResponse
    {
        $grade = Grado::findOrFail($id);
        $data = [];
        if ($request->has('name')) {
            $data['nombre'] = $request->string('name');
        }
        if ($request->has('level')) {
            $data['nivel'] = $request->string('level');
        }
        if ($request->has('order')) {
            $data['orden'] = $request->integer('order');
        }
        if ($request->has('academic_period_id')) {
            $data['periodo_academico_id'] = $request->string('academic_period_id');
        }
        $grade->update($data);

        return $this->resource($grade);
    }

    public function destroyGrade(string $id): JsonResponse
    {
        $grade = Grado::findOrFail($id);
        $grade->delete();

        return response()->json([], 204);
    }

    public function sections(Request $request)
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);
        $query = Seccion::query()->with('grado.periodoAcademico');
        $query->when($request->filled('grade_id'), fn ($q) => $q->where('grado_id', $request->string('grade_id')));
        $query->when($request->boolean('with_available_seats'), fn ($q) => $q->withCount([
            'matriculas as active_enrollments_count' => fn ($mq) => $mq->whereIn('estado', ['activo', 'activa']),
        ]));

        return AcademicResource::collection($query->paginate($this->perPage($request)));
    }

    public function storeSection(AcademicEntityRequest $request): JsonResponse
    {
        return $this->created(Seccion::create([
            'grado_id' => $request->string('grade_id'), 'nombre' => $request->string('name'),
            'capacidad' => $request->integer('capacity'), 'turno' => 'manana', 'activo' => true,
        ]));
    }

    public function updateSection(AcademicEntityRequest $request, string $id): JsonResponse
    {
        $section = Seccion::findOrFail($id);
        $data = [];
        if ($request->has('name')) {
            $data['nombre'] = $request->string('name');
        }
        if ($request->has('grade_id')) {
            $data['grado_id'] = $request->string('grade_id');
        }
        if ($request->has('capacity')) {
            $data['capacidad'] = $request->integer('capacity');
        }
        $section->update($data);

        return $this->resource($section);
    }

    public function destroySection(string $id): JsonResponse
    {
        $section = Seccion::findOrFail($id);
        $section->delete();

        return response()->json([], 204);
    }

    public function courses(Request $request)
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);
        $query = Curso::query()->with('grado')->orderBy('nombre');
        $query->when($request->filled('grade_id'), fn ($q) => $q->where('grado_id', $request->string('grade_id')));
        $query->when($request->filled('search'), fn ($q) => $q->where('nombre', 'like', '%'.$request->string('search').'%'));

        return AcademicResource::collection($query->paginate($this->perPage($request)));
    }

    public function storeCourse(AcademicEntityRequest $request): JsonResponse
    {
        $normalized = $this->normalizeName($request->string('name')->toString());
        $duplicate = Curso::query()
            ->where('grado_id', $request->string('grade_id'))
            ->where('nombre_normalizado', $normalized)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'error' => [
                    'code' => 'conflict',
                    'message' => 'El curso ya existe para el grado seleccionado.',
                    'fields' => (object) [],
                ],
            ], 409);
        }

        return $this->created(Curso::create([
            'grado_id' => $request->string('grade_id'),
            'codigo' => $request->string('code'), 'nombre' => $request->string('name'),
            'nombre_normalizado' => $this->normalizeName($request->string('name')->toString()),
            'descripcion' => $request->input('description'), 'activo' => true,
        ]));
    }

    public function updateCourse(AcademicEntityRequest $request, string $id): JsonResponse
    {
        $course = Curso::findOrFail($id);
        $data = [];
        if ($request->has('code')) {
            $data['codigo'] = $request->string('code');
        }
        if ($request->has('name')) {
            $data['nombre'] = $request->string('name');
        }
        if ($request->has('description')) {
            $data['descripcion'] = $request->input('description');
        }
        $course->update($data);

        return $this->resource($course);
    }

    public function destroyCourse(string $id): JsonResponse
    {
        $course = Curso::findOrFail($id);
        $course->delete();

        return response()->json([], 204);
    }

    public function enrollments(Request $request)
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);
        $query = Matricula::query()->with(['seccion.grado.periodoAcademico', 'alumno']);
        $query->when($request->filled('student_id'), fn ($q) => $q->where('alumno_id', $request->string('student_id')));
        $query->when($request->filled('grade_id'), fn ($q) => $q->whereHas('seccion', fn ($sq) => $sq->where('grado_id', $request->string('grade_id'))));
        $query->when($request->filled('section_id'), fn ($q) => $q->where('seccion_id', $request->string('section_id')));

        return AcademicResource::collection($query->paginate($this->perPage($request)));
    }

    public function storeEnrollment(AcademicEntityRequest $request): JsonResponse
    {
        $section = Seccion::with('grado')->findOrFail($request->string('section_id'));
        if ($section->grado->periodo_academico_id !== $request->string('academic_period_id')->toString()) {
            throw ValidationException::withMessages(['academic_period_id' => ['No corresponde a la sección seleccionada.']]);
        }
        if ($section->grado_id !== $request->string('grade_id')->toString()) {
            throw ValidationException::withMessages(['grade_id' => ['No corresponde a la sección seleccionada.']]);
        }
        $activeEnrollments = $section->matriculas()->whereIn('estado', ['activo', 'activa'])->count();
        if ($activeEnrollments >= $section->capacidad) {
            throw ValidationException::withMessages(['section_id' => ['La sección no tiene cupos disponibles.']]);
        }

        return $this->created(DB::transaction(fn () => Matricula::create([
            'alumno_id' => $request->string('student_id'), 'seccion_id' => $section->id,
            'codigo' => 'MAT-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
            'fecha' => $request->filled('enrolled_at') ? $request->date('enrolled_at')->toDateString() : now()->toDateString(),
            'estado' => 'activo', 'registrado_por' => $request->user()->id,
        ]))->load(['seccion.grado.periodoAcademico', 'alumno']));
    }

    public function updateEnrollment(AcademicEntityRequest $request, string $id): JsonResponse
    {
        $enrollment = Matricula::with('seccion.grado.periodoAcademico')->findOrFail($id);
        $data = [];
        if ($request->has('section_id')) {
            $section = Seccion::with('grado')->findOrFail($request->string('section_id'));
            if ($request->has('academic_period_id') && $section->grado->periodo_academico_id !== $request->string('academic_period_id')->toString()) {
                throw ValidationException::withMessages(['academic_period_id' => ['No corresponde a la sección seleccionada.']]);
            }
            $data['seccion_id'] = $section->id;
        }
        $enrollment->update($data);

        return $this->resource($enrollment);
    }

    public function destroyEnrollment(string $id): JsonResponse
    {
        $enrollment = Matricula::findOrFail($id);
        $enrollment->delete();

        return response()->json([], 204);
    }

    public function assignments(Request $request)
    {
        Gate::authorize('viewAny', PeriodoAcademico::class);
        $query = CargaAcademica::query()->with(['seccion.grado.periodoAcademico', 'docente', 'curso']);
        $query->when($request->filled('teacher_id'), fn ($q) => $q->where('docente_id', $request->string('teacher_id')));
        $query->when($request->filled('grade_id'), fn ($q) => $q->whereHas('seccion', fn ($sq) => $sq->where('grado_id', $request->string('grade_id'))));
        $query->when($request->filled('section_id'), fn ($q) => $q->where('seccion_id', $request->string('section_id')));
        $query->when($request->filled('course_id'), fn ($q) => $q->where('curso_id', $request->string('course_id')));

        return AcademicResource::collection($query->latest('vigente_desde')->paginate($this->perPage($request)));
    }

    public function storeAssignment(AcademicEntityRequest $request, AuditLogger $audit): JsonResponse
    {
        $section = Seccion::with('grado')->findOrFail($request->string('section_id'));
        if ($section->grado->periodo_academico_id !== $request->string('academic_period_id')->toString()) {
            throw ValidationException::withMessages(['academic_period_id' => ['No corresponde a la sección seleccionada.']]);
        }
        if ($section->grado_id !== $request->string('grade_id')->toString()) {
            throw ValidationException::withMessages(['grade_id' => ['No corresponde a la sección seleccionada.']]);
        }
        $course = Curso::findOrFail($request->string('course_id'));
        if ($course->grado_id !== null && $course->grado_id !== $section->grado_id) {
            throw ValidationException::withMessages(['course_id' => ['El curso no pertenece al grado seleccionado.']]);
        }
        $assignment = DB::transaction(function () use ($request, $section) {
            CargaAcademica::where('seccion_id', $section->id)->where('curso_id', $request->string('course_id'))
                ->where('activo', true)->update(['activo' => false, 'vigente_hasta' => now()->subDay()->toDateString()]);

            return CargaAcademica::create([
                'docente_id' => $request->string('teacher_id'), 'curso_id' => $request->string('course_id'),
                'seccion_id' => $section->id, 'vigente_desde' => now()->toDateString(), 'activo' => true,
                'asignado_por' => $request->user()->id,
            ]);
        })->load(['seccion.grado.periodoAcademico', 'docente', 'curso']);
        $audit->record($request, 'teaching_assignment.created', $request->user(), $assignment);

        return $this->created($assignment);
    }

    public function updateAssignment(AcademicEntityRequest $request, string $id): JsonResponse
    {
        $assignment = CargaAcademica::with('seccion.grado.periodoAcademico')->findOrFail($id);
        $data = [];
        if ($request->has('teacher_id')) {
            $data['docente_id'] = $request->string('teacher_id');
        }
        $assignment->update($data);

        return $this->resource($assignment);
    }

    public function destroyAssignment(string $id): JsonResponse
    {
        $assignment = CargaAcademica::findOrFail($id);
        $assignment->delete();

        return response()->json([], 204);
    }

    private function perPage(Request $request): int
    {
        return min($request->integer('per_page', 20), 100);
    }

    private function resource(object $model): JsonResponse
    {
        return response()->json(['data' => new AcademicResource($model)]);
    }

    private function created(object $model): JsonResponse
    {
        return response()->json(['data' => new AcademicResource($model)], 201);
    }

    private function statusToDatabase(string $status): string
    {
        return match ($status) {
            'active' => 'activo', 'closed' => 'cerrado', default => 'borrador',
        };
    }

    private function validateTerms(array $terms): void
    {
        $ranges = [];
        foreach ($terms as $index => $term) {
            $start = strtotime((string) ($term['start_date'] ?? ''));
            $end = strtotime((string) ($term['end_date'] ?? ''));
            if ($start === false || $end === false || $end < $start) {
                throw ValidationException::withMessages(["terms.{$index}.end_date" => ['El rango del bimestre no es válido.']]);
            }
            foreach ($ranges as [$otherStart, $otherEnd]) {
                if ($start <= $otherEnd && $end >= $otherStart) {
                    throw ValidationException::withMessages(["terms.{$index}.start_date" => ['Los bimestres no pueden solaparse.']]);
                }
            }
            $ranges[] = [$start, $end];
        }
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)->lower()->ascii()->replaceMatches('/\s+/', ' ')->trim()->toString();
    }
}

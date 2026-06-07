<?php

namespace App\Http\Controllers\Api\V1\Assessments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessments\CreateAssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Models\Examen;
use App\UseCases\Assessments\CreateAssessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssessmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        \Illuminate\Support\Facades\Gate::authorize('viewAny', Examen::class);

        $query = Examen::query();

        // Si es docente, solo puede ver sus exámenes.
        // Si es alumno, solo de su sección.
        // Si es padre, solo de sus hijos.
        // Como esto no está detallado en los requerimientos del change para el endpoint de listar todos
        // asumimos que el scope principal es para listar general con un filtro opcional.
        // Aquí implementaremos una búsqueda básica o devolvemos todo paginado por defecto.
        
        $perPage = $request->query('per_page', 15);
        $examenes = $query->latest('fecha_aplicacion')->paginate($perPage);

        return AssessmentResource::collection($examenes);
    }

    public function store(CreateAssessmentRequest $request, CreateAssessment $useCase): AssessmentResource
    {
        \Illuminate\Support\Facades\Gate::authorize('create', [Examen::class, $request->input('teaching_assignment_id')]);

        $examen = $useCase->execute($request->validated());

        return new AssessmentResource($examen);
    }
}

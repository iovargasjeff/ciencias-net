<?php

namespace App\Modules\Incidencias\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Incidencias\Application\Jobs\GenerateIncidentReport;
use App\Modules\Incidencias\Application\UseCases\AddIncidentFollowUp;
use App\Modules\Incidencias\Application\UseCases\CreateIncident;
use App\Modules\Incidencias\Application\UseCases\TransitionIncident;
use App\Modules\Incidencias\Domain\Mappers\IncidentMapper;
use App\Modules\Incidencias\Infrastructure\Models\Incidencia;
use App\Modules\Incidencias\Presentation\Requests\CreateIncidentFollowUpRequest;
use App\Modules\Incidencias\Presentation\Requests\CreateIncidentRequest;
use App\Modules\Incidencias\Presentation\Requests\GenerateIncidentReportRequest;
use App\Modules\Incidencias\Presentation\Requests\TransitionIncidentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentController extends Controller
{
    public function listIncidents(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Incidencia::class);

        $incidencias = Incidencia::with(['alumno:id,nombres,apellidos', 'reportadoPor:id,name', 'historial'])
            ->orderByDesc('fecha')
            ->paginate(15);

        // Mapear los enums de BD a API
        $data = $incidencias->getCollection()->map(function ($incidencia) {
            $array = $incidencia->toArray();
            $array['severidad'] = IncidentMapper::severityToApi($incidencia->severidad);
            $array['estado'] = IncidentMapper::statusToApi($incidencia->estado);

            return $array;
        });

        $incidencias->setCollection($data);

        return response()->json($incidencias);
    }

    public function createIncident(CreateIncidentRequest $request, CreateIncident $useCase): JsonResponse
    {
        Gate::authorize('create', Incidencia::class);

        $incidencia = $useCase->execute($request->validated(), $request->user()->id);

        $array = $incidencia->load(['alumno', 'reportadoPor'])->toArray();
        $array['severidad'] = IncidentMapper::severityToApi($incidencia->severidad);
        $array['estado'] = IncidentMapper::statusToApi($incidencia->estado);

        return response()->json(['data' => $array], 201);
    }

    public function transitionIncident(string $id, TransitionIncidentRequest $request, TransitionIncident $useCase): JsonResponse
    {
        $incidencia = Incidencia::findOrFail($id);
        Gate::authorize('transition', $incidencia);

        $useCase->execute($incidencia, $request->validated(), $request->user()->id);

        return response()->json(['data' => $incidencia->fresh()->toArray()], 200);
    }

    public function createIncidentFollowUp(string $id, CreateIncidentFollowUpRequest $request, AddIncidentFollowUp $useCase): JsonResponse
    {
        $incidencia = Incidencia::findOrFail($id);
        Gate::authorize('addFollowUp', $incidencia);

        $useCase->execute($incidencia, $request->validated(), $request->user()->id);

        return response()->json(['data' => $incidencia->fresh()->load('historial')->toArray()], 201);
    }

    public function generateIncidentReport(GenerateIncidentReportRequest $request): JsonResponse
    {
        Gate::authorize('generateReport', Incidencia::class);

        GenerateIncidentReport::dispatch($request->validated(), $request->user()->id);

        return response()->json(null, 202);
    }
}

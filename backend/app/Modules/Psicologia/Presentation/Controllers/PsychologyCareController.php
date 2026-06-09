<?php

namespace App\Modules\Psicologia\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Psicologia\Application\UseCases\CreatePsychologyCare;
use App\Modules\Psicologia\Application\UseCases\ListPsychologyCares;
use App\Modules\Psicologia\Infrastructure\Models\AtencionPsicologica;
use App\Modules\Psicologia\Presentation\Requests\CreatePsychologyCareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PsychologyCareController extends Controller
{
    public function listPsychologyCare(ListPsychologyCares $useCase): JsonResponse
    {
        Gate::authorize('viewAny', AtencionPsicologica::class);

        return response()->json($useCase->execute());
    }

    public function createPsychologyCare(CreatePsychologyCareRequest $request, CreatePsychologyCare $useCase): JsonResponse
    {
        Gate::authorize('create', AtencionPsicologica::class);

        $atencion = $useCase->execute($request->validated(), $request->user()->id);

        return response()->json(['data' => $atencion->load(['alumno', 'psicologa'])->toArray()], 201);
    }
}

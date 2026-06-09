<?php

namespace App\Modules\Academico\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academico\Application\Requests\ImportarNotasRequest;
use App\Modules\Academico\Application\Requests\RegistrarNotaRequest;
use App\Modules\Academico\Application\UseCases\ActualizarNotaUseCase;
use App\Modules\Academico\Application\UseCases\ImportarNotasMasivoUseCase;
use App\Modules\Academico\Application\UseCases\RegistrarNotaIndividualUseCase;
use App\Modules\Academico\Infrastructure\Models\Examen;
use App\Modules\Academico\Infrastructure\Models\Nota;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class NotasController extends Controller
{
    public function store(RegistrarNotaRequest $request, Examen $examen, RegistrarNotaIndividualUseCase $useCase): JsonResponse
    {
        Gate::authorize('register', [Nota::class, $examen]);

        $nota = $useCase->execute($examen, $request->validated(), $request->user()->id);

        return response()->json([
            'message' => 'Nota registrada correctamente.',
            'data' => $nota,
        ], 201);
    }

    public function import(ImportarNotasRequest $request, Examen $examen, ImportarNotasMasivoUseCase $useCase): JsonResponse
    {
        Gate::authorize('register', [Nota::class, $examen]);

        $data = $request->validated();
        $preview = $data['preview'] ?? false;

        $result = $useCase->execute($examen, $data['notas'], $request->user()->id, $preview);

        return response()->json($result, $preview ? 200 : 201);
    }

    public function update(RegistrarNotaRequest $request, Nota $nota, ActualizarNotaUseCase $useCase): JsonResponse
    {
        Gate::authorize('update', $nota);

        // Actualizamos la nota
        $notaActualizada = $useCase->execute($nota, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Nota actualizada correctamente.',
            'data' => $notaActualizada,
        ]);
    }
}

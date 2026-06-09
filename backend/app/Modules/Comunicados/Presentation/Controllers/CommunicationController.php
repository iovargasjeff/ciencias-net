<?php

namespace App\Modules\Comunicados\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comunicados\Application\UseCases\ArchiveAnnouncement;
use App\Modules\Comunicados\Application\UseCases\CreateAnnouncement;
use App\Modules\Comunicados\Application\UseCases\MarkAnnouncementRead;
use App\Modules\Comunicados\Infrastructure\Models\Comunicado;
use App\Modules\Comunicados\Presentation\Requests\CreateAnnouncementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunicationController extends Controller
{
    public function listAnnouncements(Request $request): JsonResponse
    {
        // El usuario solo puede ver comunicados donde fue incluido (vía notificación)
        // O los comunicados donde `destinatarios` incluye 'all' o roles suyos
        $userId = $request->user()->id;

        $comunicados = Comunicado::whereHas('lecturas', function ($q) use ($userId) {
            $q->where('user_id', $userId)->whereNull('archivado_en');
        })
            ->orWhere(function ($query) use ($userId) {
                $query->whereIn('id', function ($sub) use ($userId) {
                    $sub->selectRaw("CAST(datos->>'comunicado_id' AS UUID)")
                        ->from('notificaciones')
                        ->where('user_id', $userId)
                        ->where('tipo', 'comunicado');
                });
            })
            ->with('publicadoPor:id,name')
            ->orderByDesc('fecha_publicacion')
            ->paginate(15);

        return response()->json($comunicados);
    }

    public function createAnnouncement(CreateAnnouncementRequest $request, CreateAnnouncement $useCase): JsonResponse
    {
        Gate::authorize('create', Comunicado::class);

        $comunicado = $useCase->execute($request->validated(), $request->user()->id);

        return response()->json(['data' => $comunicado], 201);
    }

    public function markAnnouncementRead(string $id, Request $request, MarkAnnouncementRead $useCase): JsonResponse
    {
        $comunicado = Comunicado::findOrFail($id);

        $useCase->execute($comunicado->id, $request->user()->id);

        return response()->json(null, 204);
    }

    public function archiveAnnouncement(string $id, Request $request, ArchiveAnnouncement $useCase): JsonResponse
    {
        $comunicado = Comunicado::findOrFail($id);
        Gate::authorize('archive', $comunicado);

        $useCase->execute($comunicado->id, $request->user()->id);

        return response()->json(null, 204);
    }
}

<?php

namespace App\Modules\Comunicados\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comunicados\Application\UseCases\ArchiveAnnouncement;
use App\Modules\Comunicados\Application\UseCases\CreateAnnouncement;
use App\Modules\Comunicados\Application\UseCases\MarkAnnouncementRead;
use App\Modules\Comunicados\Infrastructure\Models\Comunicado;
use App\Modules\Comunicados\Presentation\Requests\CreateAnnouncementRequest;
use App\Modules\Notificaciones\Application\Jobs\DistributeAnnouncementNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class CommunicationController extends Controller
{
    public function listAnnouncements(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $visible = Comunicado::with(['publicadoPor:id,name', 'lecturas' => fn ($q) => $q->where('user_id', $userId)])
            ->orderByDesc('fecha_publicacion')
            ->get()
            ->filter(function (Comunicado $comunicado) use ($userId): bool {
                $reading = $comunicado->lecturas->first();
                if ($reading?->archivado_en !== null) {
                    return false;
                }

                return $reading !== null
                    || in_array($userId, (new DistributeAnnouncementNotifications($comunicado))->resolveUserIds(), true);
            })
            ->values();

        $perPage = min($request->integer('per_page', 15), 100);
        $page = max($request->integer('page', 1), 1);
        $comunicados = new LengthAwarePaginator(
            $visible->forPage($page, $perPage)->values(),
            $visible->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

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

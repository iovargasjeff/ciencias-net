<?php

namespace App\Modules\Comunicados\Application\UseCases;

use App\Modules\Comunicados\Infrastructure\Models\Comunicado;
use App\Modules\Notificaciones\Application\Jobs\DistributeAnnouncementNotifications;
use Illuminate\Support\Facades\DB;

class CreateAnnouncement
{
    public function execute(array $data, string $userId): Comunicado
    {
        return DB::transaction(function () use ($data, $userId) {
            $destinatarios = [
                $data['audience_type'] => $data['audience_ids'] ?? [],
            ];

            $comunicado = Comunicado::create([
                'titulo' => $data['title'],
                'contenido' => $data['body'],
                'destinatarios' => $destinatarios,
                'publicado_por' => $userId,
                'fecha_publicacion' => $data['publish_at'] ?? now(),
                'importante' => false,
            ]);

            DistributeAnnouncementNotifications::dispatch($comunicado);

            return $comunicado;
        });
    }
}

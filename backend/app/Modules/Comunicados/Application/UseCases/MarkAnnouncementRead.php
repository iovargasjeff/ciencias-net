<?php

namespace App\Modules\Comunicados\Application\UseCases;

use App\Modules\Comunicados\Infrastructure\Models\ComunicadoLectura;

class MarkAnnouncementRead
{
    public function execute(string $comunicadoId, string $userId): void
    {
        ComunicadoLectura::updateOrCreate(
            ['comunicado_id' => $comunicadoId, 'user_id' => $userId],
            ['leido_en' => now()]
        );
    }
}

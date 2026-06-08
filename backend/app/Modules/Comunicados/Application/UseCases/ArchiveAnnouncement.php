<?php

namespace App\Modules\Comunicados\Application\UseCases;

use App\Modules\Comunicados\Infrastructure\Models\ComunicadoLectura;

class ArchiveAnnouncement
{
    public function execute(string $comunicadoId, string $userId): void
    {
        ComunicadoLectura::updateOrCreate(
            ['comunicado_id' => $comunicadoId, 'user_id' => $userId],
            ['archivado_en' => now()]
        );
    }
}

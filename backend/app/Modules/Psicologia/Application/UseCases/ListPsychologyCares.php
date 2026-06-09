<?php

namespace App\Modules\Psicologia\Application\UseCases;

use App\Modules\Psicologia\Infrastructure\Models\AtencionPsicologica;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPsychologyCares
{
    public function execute(): LengthAwarePaginator
    {
        // En una implementación real se puede filtrar por psicóloga u otros parámetros
        return AtencionPsicologica::with(['alumno:id,nombres,apellidos', 'psicologa:id,name'])
            ->orderByDesc('fecha_atencion')
            ->paginate(15);
    }
}

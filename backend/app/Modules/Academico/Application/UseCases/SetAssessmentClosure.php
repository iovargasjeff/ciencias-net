<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Examen;

class SetAssessmentClosure
{
    public function execute(Examen $examen, bool $closed): void
    {
        $estado = $closed ? 'cerrado' : 'publicado'; // Asumimos que si se "abre" vuelve a publicado

        $examen->update([
            'estado' => $estado,
        ]);
    }
}

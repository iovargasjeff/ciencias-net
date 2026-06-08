<?php

namespace App\Modules\Materiales\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Materiales\Infrastructure\Models\Material;

class CreateExternalMaterialLink
{
    public function execute(CargaAcademica $carga, array $data, string $subidoPor): Material
    {
        return Material::create([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'tipo' => 'enlace',
            'ruta_o_url' => $data['url'],
            'carga_academica_id' => $carga->id,
            'semana' => $data['semana'] ?? null,
            'subido_por' => $subidoPor,
            'activo' => true,
        ]);
    }
}

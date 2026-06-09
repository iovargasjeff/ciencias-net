<?php

namespace App\Modules\Materiales\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Materiales\Infrastructure\Models\Material;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadMaterialFile
{
    public function execute(CargaAcademica $carga, UploadedFile $file, array $data, string $subidoPor): Material
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs("materials/{$carga->id}", $filename, 'private');

        return Material::create([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'tipo' => 'archivo',
            'ruta_o_url' => $path,
            'carga_academica_id' => $carga->id,
            'semana' => $data['semana'] ?? null,
            'subido_por' => $subidoPor,
            'activo' => true,
        ]);
    }
}

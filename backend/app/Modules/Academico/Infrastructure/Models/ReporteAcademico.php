<?php

namespace App\Modules\Academico\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteAcademico extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reportes_academicos';

    public $timestamps = false;

    protected $fillable = [
        'matricula_id', 'periodo_nombre', 'tipo', 'archivo_ruta',
        'publicado_en', 'generado_por',
    ];

    protected function casts(): array
    {
        return [
            'publicado_en' => 'datetime',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}

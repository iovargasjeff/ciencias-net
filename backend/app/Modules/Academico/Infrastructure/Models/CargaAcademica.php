<?php

namespace App\Modules\Academico\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\Docente;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargaAcademica extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'carga_academica';

    protected $fillable = [
        'seccion_id', 'curso_id', 'docente_id', 'vigente_desde', 'vigente_hasta', 'activo', 'asignado_por',
    ];

    protected function casts(): array
    {
        return ['vigente_desde' => 'date', 'vigente_hasta' => 'date', 'activo' => 'boolean'];
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}

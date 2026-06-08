<?php

namespace App\Modules\Asistencia\Domain\Models;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionClase extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sesiones_clase';

    protected $fillable = ['carga_academica_id', 'fecha', 'hora_inicio', 'hora_fin', 'estado', 'motivo_cancelacion', 'cancelada_por', 'docente_sustituto_id', 'revisado_planilla_por'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function cargaAcademica(): BelongsTo
    {
        return $this->belongsTo(CargaAcademica::class, 'carga_academica_id');
    }
}

<?php

namespace App\Modules\Horarios\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'horarios';

    protected $fillable = [
        'carga_academica_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'aula',
    ];

    public function cargaAcademica(): BelongsTo
    {
        return $this->belongsTo(CargaAcademica::class);
    }
}

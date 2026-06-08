<?php

namespace App\Modules\Horarios\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoCalendario extends Model
{
    use HasUuids;

    protected $table = 'eventos_calendario';

    public $timestamps = false;

    protected $fillable = [
        'periodo_academico_id',
        'tipo',
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'seccion_id',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}

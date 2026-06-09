<?php

namespace App\Modules\Incidencias\Infrastructure\Models;

use App\Modules\Psicologia\Infrastructure\Models\AtencionPsicologica;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incidencia extends Model
{
    use HasUuids;

    protected $table = 'incidencias';

    protected $fillable = [
        'alumno_id',
        'reportado_por',
        'fecha',
        'tipo',
        'severidad',
        'descripcion',
        'asignado_a',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialIncidencia::class, 'incidencia_id');
    }

    public function atencionesPsicologicas(): HasMany
    {
        return $this->hasMany(AtencionPsicologica::class, 'incidencia_id');
    }
}

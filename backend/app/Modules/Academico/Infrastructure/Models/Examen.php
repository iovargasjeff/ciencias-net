<?php

namespace App\Modules\Academico\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Nota;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'examenes';

    protected $fillable = [
        'carga_academica_id', 'titulo', 'fecha_aplicacion', 'assessment_type',
        'channel', 'total_preguntas', 'puntaje_maximo', 'estado',
        'publicado_por', 'publicado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date',
            'puntaje_maximo' => 'decimal:2',
            'publicado_en' => 'datetime',
        ];
    }

    public function cargaAcademica(): BelongsTo
    {
        return $this->belongsTo(CargaAcademica::class);
    }

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicado_por');
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class);
    }
}

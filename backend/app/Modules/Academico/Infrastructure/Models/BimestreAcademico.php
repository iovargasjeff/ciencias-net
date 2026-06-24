<?php

namespace App\Modules\Academico\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BimestreAcademico extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'bimestres_academicos';

    protected $fillable = ['periodo_academico_id', 'nombre', 'fecha_inicio', 'fecha_fin'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }
}

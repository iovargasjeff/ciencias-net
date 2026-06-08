<?php

namespace App\Models;

use App\Modules\Academico\Infrastructure\Models\Examen;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nota extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notas';

    protected $fillable = [
        'examen_id', 'matricula_id', 'puntaje', 'estado',
        'observacion', 'puesto_ranking', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'puntaje' => 'decimal:2',
        ];
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}

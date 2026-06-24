<?php

namespace App\Modules\Academico\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matricula extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['alumno_id', 'seccion_id', 'codigo', 'fecha', 'estado', 'registrado_por'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function cargasAcademicas(): BelongsToMany
    {
        return $this->belongsToMany(CargaAcademica::class, 'matricula_carga_academica')
            ->withPivot('created_at');
    }
}

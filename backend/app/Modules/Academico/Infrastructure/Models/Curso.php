<?php

namespace App\Modules\Academico\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['codigo', 'nombre', 'area', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function cargasAcademicas(): HasMany
    {
        return $this->hasMany(CargaAcademica::class);
    }
}

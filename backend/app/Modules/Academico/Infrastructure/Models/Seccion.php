<?php

namespace App\Modules\Academico\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\Matricula;

use App\Modules\Academico\Infrastructure\Models\Grado;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seccion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'secciones';

    protected $fillable = ['grado_id', 'nombre', 'turno', 'aula', 'capacidad', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }
}

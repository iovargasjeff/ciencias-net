<?php

namespace App\Modules\Usuarios\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'dni', 'nombres', 'apellidos', 'telefono'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cargasAcademicas(): HasMany
    {
        return $this->hasMany(CargaAcademica::class);
    }
}

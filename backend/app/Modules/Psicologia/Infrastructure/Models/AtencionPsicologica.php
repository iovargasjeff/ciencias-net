<?php

namespace App\Modules\Psicologia\Infrastructure\Models;

use App\Modules\Incidencias\Infrastructure\Models\Incidencia;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtencionPsicologica extends Model
{
    use HasUuids;

    protected $table = 'atenciones_psicologia';

    protected $fillable = [
        'incidencia_id',
        'alumno_id',
        'psicologa_id',
        'fecha_atencion',
        'notas_privadas',
    ];

    protected $casts = [
        'fecha_atencion' => 'datetime',
    ];

    public function incidencia(): BelongsTo
    {
        return $this->belongsTo(Incidencia::class, 'incidencia_id');
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function psicologa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'psicologa_id');
    }
}

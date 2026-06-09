<?php

namespace App\Modules\Finanzas\Infrastructure\Models;

use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionFinanciera extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'configuraciones_financieras';

    protected $fillable = [
        'periodo_academico_id',
        'dia_generacion_mensualidad',
        'dia_vencimiento_mensualidad',
        'configurado_por',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function configuradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configurado_por');
    }
}

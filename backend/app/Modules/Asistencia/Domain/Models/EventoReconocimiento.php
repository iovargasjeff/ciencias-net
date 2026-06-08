<?php

namespace App\Modules\Asistencia\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoReconocimiento extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'eventos_reconocimiento';

    protected $fillable = ['idempotency_key', 'estacion_id', 'camara_estacion_id', 'cuenta_tecnica_id', 'user_id', 'tipo_persona', 'tipo_evento_resuelto', 'confianza', 'prueba_vida_superada', 'estado', 'motivo_estado', 'evidencia_archivo_id', 'capturado_en', 'recibido_en', 'revisado_por', 'revisado_en'];

    protected function casts(): array
    {
        return ['confianza' => 'decimal:4', 'prueba_vida_superada' => 'boolean', 'capturado_en' => 'datetime', 'recibido_en' => 'datetime', 'revisado_en' => 'datetime'];
    }

    public function estacion(): BelongsTo
    {
        return $this->belongsTo(EstacionBiometrica::class, 'estacion_id');
    }

    public function camara(): BelongsTo
    {
        return $this->belongsTo(CamaraEstacion::class, 'camara_estacion_id');
    }

    public function cuentaTecnica(): BelongsTo
    {
        return $this->belongsTo(CuentaTecnica::class, 'cuenta_tecnica_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

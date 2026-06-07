<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PK compuesta: (comunicado_id, user_id). No usa HasUuids.
 */
class ComunicadoLectura extends Model
{
    use HasFactory;

    protected $table = 'comunicado_lecturas';

    public $incrementing = false;
    protected $primaryKey = null; // PK compuesta gestionada manualmente
    public $timestamps = false;

    protected $fillable = ['comunicado_id', 'user_id', 'leido_en', 'archivado_en'];

    protected function casts(): array
    {
        return [
            'leido_en'     => 'datetime',
            'archivado_en' => 'datetime',
        ];
    }

    public function comunicado(): BelongsTo
    {
        return $this->belongsTo(Comunicado::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

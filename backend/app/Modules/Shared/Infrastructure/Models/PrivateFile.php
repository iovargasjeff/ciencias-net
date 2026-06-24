<?php

namespace App\Modules\Shared\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateFile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'purpose',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'metadata',
        'uploaded_by',
        'expires_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }
}

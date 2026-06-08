<?php

namespace App\Modules\Usuarios\Infrastructure\Models;

use App\Modules\Usuarios\Infrastructure\Models\Padre;

use App\Modules\Usuarios\Infrastructure\Models\Alumno;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'activo', 'ultimo_login'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'activo' => 'boolean',
            'ultimo_login' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function padre(): HasOne
    {
        return $this->hasOne(Padre::class);
    }

    public function alumno(): HasOne
    {
        return $this->hasOne(Alumno::class);
    }
}

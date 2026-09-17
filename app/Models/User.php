<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'name',
        'email',
        'password',
        'rol',
        'telefono',
        'avatar',
        'activo',
        'is_super',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'is_super' => 'boolean',
        ];
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esSuper(): bool
    {
        return (bool) $this->is_super;
    }
}

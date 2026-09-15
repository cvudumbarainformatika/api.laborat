<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserKencana extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'users_kencana';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'email',
        'role',
        'aktif',
    ];

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}

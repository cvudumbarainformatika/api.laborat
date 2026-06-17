<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'source',
        'noreg',
        'layanan',
    ];


    public function user()
    {
        return $this->belongsTo(\App\Models\Simpeg\Petugas::class, 'user_id', 'id');
    }
}

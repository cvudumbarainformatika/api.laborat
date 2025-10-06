<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerahTerima extends Model
{
    use HasFactory;
    protected $table = 'serah_terima';
    protected $guarded = ['id'];
    protected $casts = [
        'terapis' => 'array',
    ];
}

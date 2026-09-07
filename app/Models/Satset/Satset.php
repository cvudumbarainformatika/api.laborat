<?php

namespace App\Models\Satset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class Satset extends Model
{
    use HasFactory;
    protected $table = 'satsets';
    protected $guarded = ['id'];
    protected $casts = [
        'response' => 'array'
    ];
}

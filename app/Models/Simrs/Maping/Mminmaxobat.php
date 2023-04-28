<?php

namespace App\Models\Simrs\Maping;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mminmaxobat extends Model
{
    use HasFactory;
    protected $table = 'min_max_ruang';
    protected $guarded = ['id'];
}

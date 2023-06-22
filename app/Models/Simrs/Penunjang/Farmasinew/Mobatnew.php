<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mobatnew extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'new_masterobat';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

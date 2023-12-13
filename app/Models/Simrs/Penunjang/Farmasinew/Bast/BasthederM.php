<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Bast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasthederM extends Model
{
    use HasFactory;
    protected $table = 'bast_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

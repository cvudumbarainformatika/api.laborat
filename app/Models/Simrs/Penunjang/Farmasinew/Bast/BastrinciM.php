<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Bast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BastrinciM extends Model
{
    use HasFactory;
    protected $table = 'bast_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

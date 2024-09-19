<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturGudangDetail extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

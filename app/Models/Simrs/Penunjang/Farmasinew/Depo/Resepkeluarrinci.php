<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resepkeluarrinci extends Model
{
    use HasFactory;
    protected $table = 'resep_keluar_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

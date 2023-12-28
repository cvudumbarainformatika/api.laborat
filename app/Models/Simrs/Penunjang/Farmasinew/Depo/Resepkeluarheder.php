<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resepkeluarheder extends Model
{
    use HasFactory;
    protected $table = 'resep_keluar_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

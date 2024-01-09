<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaanresep extends Model
{
    use HasFactory;
    protected $table = 'resep_permintaan_keluar';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

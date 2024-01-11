<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaanresepracikan extends Model
{
    use HasFactory;
    protected $table = 'resep_permintaan_keluar_racikan';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

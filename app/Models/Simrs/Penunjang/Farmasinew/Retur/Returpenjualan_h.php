<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returpenjualan_h extends Model
{
    use HasFactory;
    protected $table = 'retur_penjualan_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rinci()
    {
        return $this->hasMany(Returpenjualan_r::class, 'noretur', 'noretur');
    }

    public function dokter()
    {
        return $this->hasone(Petugas::class, 'kdpegsimrs', 'kddokter');
    }

    public function datapasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'norm');
    }

    public function poli()
    {
        return $this->belongsTo(Mpoli::class, 'kdruangan', 'rs1');
    }

    public function ruanganranap()
    {
        return $this->belongsTo(Mruangranap::class, 'kdruangan', 'rs1');
    }
}

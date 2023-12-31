<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpasien;
use App\Models\SistemBayar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resepkeluarheder extends Model
{
    use HasFactory;
    protected $table = 'resep_keluar_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rincian()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'nota', 'nota');
    }

    public function dokter()
    {
        return $this->hasone(Pegawai::class, 'kdpegsimrs', 'dokter');
    }

    public function sistembayar()
    {
        return $this->hasone(SistemBayar::class, 'rs1', 'sistembayar');
    }

    public function datapasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'norm');
    }
}

<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Penunjang\Farmasinew\PelayananInformasiObat;
use App\Models\Simrs\Ranap\Mruangranap;
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
        return $this->hasMany(Resepkeluarrinci::class, 'noresep', 'noresep');
    }
    public function rincianracik()
    {
        return $this->hasMany(Resepkeluarrinciracikan::class, 'noresep', 'noresep');
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

    public function asalpermintaanresep()
    {
        return $this->hasMany(Permintaanresep::class, 'noresep', 'noresep_asal');
    }
    public function permintaanresep()
    {
        return $this->hasMany(Permintaanresep::class, 'noresep', 'noresep');
    }
    public function asalpermintaanracikan()
    {
        return $this->hasMany(Permintaanresepracikan::class, 'noresep', 'noresep_asal');
    }
    public function permintaanracikan()
    {
        return $this->hasMany(Permintaanresepracikan::class, 'noresep', 'noresep');
    }
    public function poli()
    {
        return $this->belongsTo(Mpoli::class, 'ruangan', 'rs1');
    }

    public function ruanganranap()
    {
        return $this->belongsTo(Mruangranap::class, 'ruangan', 'rs1');
    }
    public function info()
    {
        return $this->belongsTo(PelayananInformasiObat::class, 'noreg', 'noreg');
    }
    public function sep()
    {
        return $this->belongsTo(Seprajal::class, 'noreg', 'rs1');
    }
}

<?php

namespace App\Models\Simrs\Ranap;

use App\Models\Simrs\Kasir\Biayamaterai;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mruangan;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penunjang\Gizi\AsuhanGizi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Oksigen\Oksigen;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjunganranap extends Model
{
    use HasFactory;
    protected $table = 'rs23';
    protected $gurded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function relmasterruangranap()
    {
        return $this->hasOne(Mruangranap::class, 'rs1', 'rs5');
    }

    public function relsistembayar()
    {
        return $this->hasOne(Msistembayar::class, 'rs1', 'rs19');
    }

    public function reldokter()
    {
        return $this->hasOne(Dokter::class, 'rs1', 'rs10');
    }

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    public function rstigalimax()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1');
    }
    public function rstigalimaxx()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1')->take(2);
    }

    public function akomodasikamar()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1')->take(2);
    }

    public function biayamaterai()
    {
        return $this->hasMany(Biayamaterai::class, 'rs1','rs1');
    }

    public function tindakandokter()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function visiteumum()
    {
        return $this->hasMany(Visite::class, 'rs1', 'rs1');
    }

    public function tindakanperawat()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function asuhangizi()
    {
        return $this->hasMany(AsuhanGizi::class, 'rs1','rs1');
    }

    public function makanpasien()
    {
        return $this->hasMany(AsuhanGizi::class, 'rs1','rs1');
    }

    public function oksigen()
    {
        return $this->hasMany(Oksigen::class, 'rs1','rs1');
    }

    public function keperawatan()
    {
        return $this->hasMany(Oksigen::class, 'rs1','rs1');
    }

    public function laborat()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function transradiologi()
    {
        return $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    public function tindakanendoscopy()
    {
        return $this->hasMany(Tindakan::class, 'rs1','rs1');
    }

    public function kamaroperasiibs()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1','rs1');
    }


}

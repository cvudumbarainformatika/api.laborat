<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Laporan\Operasi\LaporanOperasi;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penunjang\Bankdarah\PermintaanBankdarah;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\PembacaanradiologiController;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Rajal\Memodiagnosadokter;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Mruangranap;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanOperasi extends Model
{
    use HasFactory;
    protected $table = 'rs200';
    protected $connection = 'mysql';
    protected $guarded = ['id'];

    public function kunjunganranap()
    {
        return $this->hasOne(Kunjunganranap::class, 'rs1', 'rs1');
    }


    public function kunjunganrajal()
    {
        return $this->hasOne(KunjunganPoli::class, 'rs1', 'rs1');
    }

    public function sistembayar()
    {
        return $this->hasOne(Msistembayar::class, 'rs1', 'rs14');
    }

    public function dokter()
    {
        return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'rs8');
    }
    public function permintaanobatoperasi()
    {
        return $this->hasMany(PersiapanOperasi::class, 'noreg', 'rs1');
    }
    public function newapotekrajal()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }
    public function manymemo()
    {
        return $this->hasMany(Memodiagnosadokter::class, 'noreg', 'rs1');
    }
    public function manytindakanop()
    {
        return $this->hasMany(Kamaroperasi::class, 'rs1', 'rs1');
    }
    public function surgical()
    {
        return $this->hasMany(SurgicalSafety::class, 'noreg', 'rs1');
    }
    public function ruangranap()
    {
        return $this->hasOne(Mruangranap::class, 'rs1', 'rs10');
    }
    public function tindakanop()
    {
        return $this->hasOne(Kamaroperasi::class, 'rs2', 'rs2');
    }
    public function laporanop()
    {
        return $this->hasMany(LaporanOperasi::class, 'rs1', 'rs1');
    }
    public function implant()
    {
        return $this->hasMany(Implant::class, 'nota', 'rs2');
    }
    public function implant_seri()
    {
        return $this->hasMany(ImplantSeri::class, 'nota', 'rs2');
    }
    public function inventaris_kasa()
    {
        return $this->hasMany(InventarisKasa::class, 'nota', 'rs2');
    }
    public function inventaris_instrumen()
    {
        return $this->hasMany(InventarisInstrumen::class, 'nota', 'rs2');
    }
    public function pra_bedah()
    {
        return $this->belongsTo(AssasemenPraBedah::class, 'rs2', 'nota');
    }
    public function pra_induksi()
    {
        return $this->belongsTo(AssasmentPraInduksi::class, 'rs2', 'nota');
    }

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }
<<<<<<< HEAD
=======

    public function laborats()
    {
        return $this->hasMany(LaboratMeta::class, 'noreg', 'rs1');
    }
    public function laboratold()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function bankdarah()
    {
        return $this->hasMany(PermintaanBankdarah::class, 'rs1', 'rs1');
    }

    public function radiologi()
    {
        return $this->hasMany(Transpermintaanradiologi::class, 'rs1', 'rs1');
    }
    public function hasilradiologi()
    {
        return $this->hasMany(PembacaanradiologiController::class, 'rs1', 'rs1');
    }
>>>>>>> 4d881e546775f6373e165a298de9bafbe05c05c0
}

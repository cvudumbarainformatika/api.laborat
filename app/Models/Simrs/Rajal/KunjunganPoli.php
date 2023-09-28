<?php

namespace App\Models\Simrs\Rajal;

use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use App\Models\Simrs\Pemeriksaanfisik\Simpangambarpemeriksaanfisik;
use App\Models\Simrs\Pendaftaran\Mgeneralconsent;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Pendaftaran\Rajalumum\Taskidantrian;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Kamaroperasi\PermintaanOperasi;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Lain\Lain;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Rekom\Rekomdpjp;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganPoli extends Model
{
    use HasFactory;
    protected $table = 'rs17';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    // public function relrekomdpjp()
    // {
    //     return $this->hasMany(Rekomdpjp::class, 'rs1', 'noreg');
    // }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs8', 'rs1');
    }

    public function msistembayar()
    {
        return $this->belongsTo(Msistembayar::class, 'rs14', 'rs1');
    }

    public function dokter()
    {
        return $this->hasOne(Dokter::class, 'rs1', 'rs9');
    }

    public function seprajal()
    {
        return $this->hasOne(Seprajal::class, 'rs1', 'rs1');
    }

    public function generalconsent()
    {
        return $this->hasOne(Mgeneralconsent::class, 'noreg', 'rs1');
    }

    public function taskid()
    {
        return $this->hasMany(Taskidantrian::class, 'noreg', 'rs1');
    }

    public function anamnesis()
    {
        return $this->hasMany(Anamnesis::class, 'rs1', 'rs1');
    }
    public function pemeriksaanfisik()
    {
        return $this->hasMany(Pemeriksaanfisik::class, 'rs1', 'rs1');
    }
    public function gambars()
    {
        return $this->hasMany(Simpangambarpemeriksaanfisik::class, 'noreg', 'rs1');
    }
    public function diagnosa()
    {
        return $this->hasMany(Diagnosa::class, 'rs1', 'rs1');
    }
    public function tindakan()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'rs1');
    }
    public function laborats()
    {
        return $this->hasMany(LaboratMeta::class, 'noreg', 'rs1');
    }
    public function radiologi()
    {
        return $this->hasMany(Transpermintaanradiologi::class, 'rs1', 'rs1');
    }
    public function penunjanglain()
    {
        return $this->hasMany(Lain::class, 'rs1', 'rs1');
    }
    public function ok()
    {
        return $this->hasMany(PermintaanOperasi::class, 'rs1', 'rs1');
    }
}

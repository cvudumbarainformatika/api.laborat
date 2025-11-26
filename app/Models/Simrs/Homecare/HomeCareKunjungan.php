<?php

namespace App\Models\Simrs\Homecare;

use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Edukasi\ImplementasiEdukasi;
use App\Models\Simrs\Edukasi\Transedukasi;
use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Tindakan\Tindakan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeCareKunjungan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'mysql';


    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'norm');
    }

    public function poli()
    {
        return $this->belongsTo(Mpoli::class, 'kode_poli', 'rs1');
    }
    public function dokter()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'dpjp');
    }

    public function newapotekrajal()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'noreg');
    }
    public function laborats()
    {
        return $this->hasMany(LaboratMeta::class, 'noreg', 'noreg');
    }
    public function laboratold()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'noreg');
    }
    public function fisio()
    {
        return $this->hasMany(Fisioterapipermintaan::class, 'rs1', 'noreg');
    }
    public function anamnesis()
    {
        return $this->hasMany(Anamnesis::class, 'rs1', 'noreg');
    }
    public function tindakan()
    {
        return $this->hasMany(Tindakan::class, 'rs1', 'noreg');
    }

    public function diagnosa()
    {
        return $this->hasMany(Diagnosa::class, 'rs1', 'noreg');
    }

    public function edukasi()
    {
        return $this->hasMany(Transedukasi::class, 'rs1', 'noreg');
    }

    public function rs239_implementasi()
    {
        return $this->hasMany(ImplementasiEdukasi::class, 'noreg', 'noreg');
    }
}

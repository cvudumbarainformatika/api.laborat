<?php

namespace App\Models\Simrs\Penunjang\Fisioterapi;

use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\PembacaanradiologiController;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fisioterapipermintaan extends Model
{
    use HasFactory;
    protected $table = 'rs201';
    protected $guarded = ['id'];

    public function laborats()
    {
        return $this->hasMany(LaboratMeta::class, 'noreg', 'rs1');
    }

     public function laboratold()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }

    public function radiologi()
    {
        return $this->hasMany(Transpermintaanradiologi::class, 'rs1', 'rs1');
    }

    public function transradiologi()
    {
        return $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    public function hasilradiologi()
    {
        return $this->hasMany(PembacaanradiologiController::class, 'rs1', 'rs1');
    }
     public function newapotekrajal()
    {
        // return $this->hasOne(Resepkeluarheder::class, 'noreg', 'rs1');
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'noreg');
    }
    public function diagnosa()
    {
        return $this->hasMany(Diagnosa::class, 'rs1', 'rs1');
    }

}

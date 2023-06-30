<?php

namespace App\Models\Simrs\Rajal;

use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Pendaftaran\Mgeneralconsent;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Pendaftaran\Rajalumum\Taskidantrian;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Rekom\Rekomdpjp;
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
        return $this->hasOne(Dokter::class,'rs1','rs9');
    }

    public function seprajal()
    {
        return $this->hasOne(Seprajal::class,'rs1', 'rs1');
    }

    public function generalconsent()
    {
        return $this->hasOne(Mgeneralconsent::class, 'noreg', 'rs1');
    }

    public function taskid()
    {
        return $this->hasMany(Taskidantrian::class, 'noreg', 'rs1');
    }

}

<?php

namespace App\Models\Simrs\Billing\Rajal;

use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanrincilalu;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allbillrajal extends Model
{
    use HasFactory;
    protected $table = 'rs17';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function masterpasien()
    {
        return $this->hasMany(Mpasien::class, 'rs1', 'rs2');
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs8', 'rs1');
    }

    public function msistembayar()
    {
        return $this->belongsTo(Msistembayar::class, 'rs14', 'rs1');
    }

    public function apotekrajalpolilalu()
    {
        return $this->hasMany(Apotekrajallalu::class, 'rs1', 'rs1');
    }

    public function apotekracikanrajal()
    {
        return $this->hasOneThrough(
            Apotekrajalracikanrincilalu::class,
            Apotekrajalracikanhedlalu::class,
            'rs1','rs1'
        );
    }

    public function laborat()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs1', 'rs1');
    }
}

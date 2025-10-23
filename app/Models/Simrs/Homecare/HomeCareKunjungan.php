<?php

namespace App\Models\Simrs\Homecare;

use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeCareKunjungan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'mysql';



    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs8', 'rs1');
    }
    public function dokter()
    {
        return $this->hasOne(Dokter::class, 'rs1', 'rs9');
    }

    public function newapotekrajal()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }
}

<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersiapanOperasiDistribusi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }

    public function persiapan(){
        return $this->belongsTo(PersiapanOperasi::class,'nopermintaan','nopermintaan');
    }
    public function master(){
        return $this->belongsTo(Mobatnew::class,'kd_obat','kd_obat');
    }
}

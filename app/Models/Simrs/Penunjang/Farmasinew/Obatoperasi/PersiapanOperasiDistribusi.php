<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi;

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
}

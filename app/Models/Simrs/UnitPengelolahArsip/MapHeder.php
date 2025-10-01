<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use App\Models\Arsip\Master\MkelasifikasiArsip;
use App\Models\MorganisasiAdministrasi;
use App\Models\Simrs\Master\Mcabinet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapHeder extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'kelompokMap_H';
    protected $guarded = ['id'];

    public function rinciandalammap()
    {
        return $this->hasMany(MapRincian::class, 'id_heder', 'id');
    }

     public function klasifikasi()
    {
        return $this->hasOne(MkelasifikasiArsip::class, 'kode', 'kodeklasifikasi');
    }
    public function unitpengolah()
    {
        return $this->hasOne(MorganisasiAdministrasi::class, 'kode', 'kodeorganisasi');
    }

    public function kabinet()
    {
        return $this->hasOne(Mcabinet::class, 'id', 'kodefelingkabinet');
    }
}

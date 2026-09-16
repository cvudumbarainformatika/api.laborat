<?php

namespace App\Models\Siasik\Anggaran;

use App\Models\Siasik\Master\Akun_mapjurnal;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Rinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perubahan_pak_rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'usulanHonor_r_pak';


    public function jurnal()
    {
        return $this->hasOne(Akun_mapjurnal::class, 'kodeall', 'koderek50');
    }

    public function jurnalkode50()
    {
        return $this->hasOne(Akun_mapjurnal::class, 'kode50', 'koderek50');
    }

     public function getDataJurnalAttribute()
    {
        return $this->jurnal ?? $this->jurnalkode50;
    }

    protected $appends = ['jenis', 'datajurnal'];
    
    protected $hidden = [
        'jurnalkode50',
    ];



    public function realisasi(){
        return $this->hasMany(NpdLS_rinci::class, 'idserahterima_rinci', 'idpp');
    }
    public function realisasi_spjpanjar(){
        return $this->hasMany(SpjPanjar_Rinci::class, 'iditembelanjanpd', 'idpp');
    }
    public function contrapost(){
        return $this->hasMany(Contrapost::class,'idpp', 'idpp');
    }
}

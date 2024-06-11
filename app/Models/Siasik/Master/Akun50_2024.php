<?php

namespace App\Models\Siasik\Master;

use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Rinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akun50_2024 extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'akun50_2024';
    public $timestamp = false;
    protected $appends = ['kodeall'];
    public function getKodeallAttribute(){
        return "{$this->akun}.{$this->kelompok}.{$this->jenis}.{$this->objek}.{$this->rincian_objek}.{$this->subrincian_objek}";
    }
    public function npdls_rinci(){
        return $this->hasMany(NpdLS_rinci::class,'koderek50', 'kodeall2');
    }
    public function spjpanjar(){
        return $this->hasMany(SpjPanjar_Rinci::class,'koderek50', 'kodeall2');
    }
    public function cp(){
        return $this->hasMany(Contrapost::class,'koderek50', 'kodeall2');
    }
    public function anggaran(){
        return $this->hasMany(PergeseranPaguRinci::class,'koderek50', 'kodeall2');
    }
}

<?php

namespace App\Models\Siasik\Anggaran;

use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpdLS_rinci;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Rinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PergeseranPaguRinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 't_tampung';
    public $timestamp = false;

    public function npdls_rinci(){
        return $this->hasMany(NpdLS_rinci::class,'koderek50', 'koderek50');
    }
    public function spjpanjar(){
        return $this->hasMany(SpjPanjar_Rinci::class,'koderek50', 'koderek50');
    }
    public function cp(){
        return $this->hasMany(Contrapost::class,'koderek50', 'koderek50');
    }

}

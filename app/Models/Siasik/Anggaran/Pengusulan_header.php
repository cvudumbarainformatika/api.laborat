<?php

namespace App\Models\Siasik\Anggaran;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengusulan_header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'usulanHonor_h';

    public function rincian(){
        return $this->hasMany(Pengusulan_rinci::class,'notrans', 'notrans');
    }

    // public function pergeseranpak(){
    //     return $this->hasMany(Perubahan_RincianBelanja::class,'notrans', 'notrans');
    // }
}

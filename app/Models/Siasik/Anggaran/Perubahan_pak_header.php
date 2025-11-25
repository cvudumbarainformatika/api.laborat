<?php

namespace App\Models\Siasik\Anggaran;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perubahan_pak_header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'usulanHonor_h_pak';

    public function rincipak(){
        return $this->hasMany(Perubahan_pak_rinci::class,'notrans', 'notrans');
    }

    public function pergeseranpak(){
        return $this->hasMany(Perubahan_RincianBelanja::class,'notrans', 'notrans');
    }
}

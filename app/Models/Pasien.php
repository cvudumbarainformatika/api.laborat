<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    use HasFactory;
    protected $table = 'rs15';

    public function kunjungan_rawat_inap()
    {
        return $this->hasMany(KunjunganRawatInap::class,'rs2');

    }
    public function kunjungan_poli()
    {
        return $this->hasMany(KunjunganPoli::class,'rs2');

    }
}

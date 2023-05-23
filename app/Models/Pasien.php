<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    use HasFactory;
    protected $table = 'rs15';
    protected $appends = ['usia'];

    public function getUsiaAttribute()
    {
        $dateOfBirth = $this->rs16;
        $years = Carbon::parse($dateOfBirth)->age;
        $month = Carbon::parse($dateOfBirth)->month;
        $day = Carbon::parse($dateOfBirth)->day;
        return $years . " Tahun, " . $month . " Bulan, " . $day . " Hari";
    }

    public function kunjungan_rawat_inap()
    {
        return $this->hasMany(KunjunganRawatInap::class, 'rs2');
    }
    public function kunjungan_poli()
    {
        return $this->hasMany(KunjunganPoli::class, 'rs2');
    }
}

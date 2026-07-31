<?php

namespace App\Models;

use App\Models\Simrs\Master\Mwilayah;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    use HasFactory;
    protected $table = 'rs15';
    protected $primaryKey = 'rs1';
    protected $appends = ['usia'];


    public function getWilayahAttribute()
    {
        return Mwilayah::where([
            'kode2' => $this->kd_propinsi,
            'kode3' => $this->kd_kota,
            'kode4' => $this->kd_kec,
            'kode5' => $this->kd_kel,
        ])->first();
    }

    public function getUsiaAttribute()
    {
        $dateOfBirth = $this->rs16 ?? $this->tgllahir ?? null;
        if (!$dateOfBirth) {
            return "-";
        }
        $birth = Carbon::parse($dateOfBirth);
        $diff = $birth->diff(Carbon::now());
        return $diff->y . " Tahun " . $diff->m . " Bulan " . $diff->d . " Hari";
    }

    public function scopeGetByNoBpjs($query, $nobpjs)
    {
        $query->when($nobpjs ?? false, function ($search, $req) {
            return $search->where('rs46', $req);
        });
    }
    public function scopeGetByNik($query, $nik)
    {
        $query->when($nik ?? false, function ($search, $req) {
            return $search->where('rs49', $req);
        });
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

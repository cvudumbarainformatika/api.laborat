<?php

namespace App\Models;

use App\Models\Antrean\JadwalPoliCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs19';
    protected $guarded = ['rs1'];

    public function kunjungan_poli()
    {
        return $this->hasMany(KunjunganPoli::class, 'rs8', 'rs1');
    }

    /**
     * Relasi ke model JadwalPoliCache (tabel jadwal_poli_cache) berdasarkan kode BPJS (rs6 = kode_poli)
     */
    public function jadwal_poli_cache()
    {
        return $this->hasMany(JadwalPoliCache::class, 'kode_poli', 'rs6');
    }
}

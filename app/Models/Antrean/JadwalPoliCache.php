<?php

namespace App\Models\Antrean;

use App\Models\Poli;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPoliCache extends Model
{
    use HasFactory;
    protected $connection = 'newantrean';
    protected $table = 'jadwal_poli_cache';
    protected $guarded = ['id'];

    /**
     * Relasi ke model Poli (tabel rs19 di db mysql) berdasarkan kode BPJS (kode_poli = rs6)
     */
    public function poli()
    {
        $instance = new Poli();
        $instance->setConnection('mysql');

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            'kode_poli',
            'rs6',
            'poli'
        );
    }

    /**
     * Relasi ke model Petugas/Pegawai (tabel pegawai di db kepex) berdasarkan kddpjp
     */
    public function pegawai()
    {
        $instance = new Petugas();
        $instance->setConnection('kepex');

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            'kode_dokter',
            'kddpjp',
            'pegawai'
        )->select('nik', 'nip', 'nama', 'kdpegsimrs', 'kddpjp', 'foto');
    }

    /**
     * Alias relasi ke pegawai dengan nama dokter
     */
    public function dokter()
    {
        return $this->pegawai();
    }
}

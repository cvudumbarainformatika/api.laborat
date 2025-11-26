<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use App\Models\MorganisasiAdministrasi;
use App\Models\Pegawai\Mpegawaisimpeg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeminjamanHeder extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'tpeminjaman_h';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'petugas');
    }

    public function userpeminjam()
    {
        return $this->hasOne(Mpegawaisimpeg::class, 'nik', 'peminjam');
    }

    public function unitpengolahx()
    {
        return $this->hasOne(MorganisasiAdministrasi::class, 'kode', 'unitpengolah');
    }
}

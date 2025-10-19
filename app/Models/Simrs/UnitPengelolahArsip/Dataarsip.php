<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use App\Models\Arsip\Master\MkelasifikasiArsip;
use App\Models\Arsip\Master\MmediaArsip;
use App\Models\MorganisasiAdministrasi;
use App\Models\Pegawai\Mpegawaisimpeg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataarsip extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'data_arsip';
    protected $guarded = ['id'];

    public function unitpengolah()
    {
        return $this->hasOne(MorganisasiAdministrasi::class, 'kode', 'unit_pengolah');
    }

    public function user()
    {
        return $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'username');
    }

    public function klasifikasi()
    {
        return $this->hasOne(MkelasifikasiArsip::class, 'kode', 'kode');
    }

    public function media()
    {
        return $this->hasOne(MmediaArsip::class, 'id', 'media');
    }

    public function rincianmap()
    {
        return $this->hasOne(MapRincian::class, 'noarsip', 'noarsip');
    }

    public function caripeminjaman()
    {
        return $this->hasOne(PeminjamanHeder::class, 'noarsip', 'noarsip');
    }
}

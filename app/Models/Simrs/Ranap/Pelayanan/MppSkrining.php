<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Simpeg\Petugas;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MppSkrining extends Model
{
    use HasFactory, LogsActivity;

    protected $connection = 'mysql';
    protected $table = 'mpp_skrinings';
    protected $guarded = ['id'];

    protected $casts = [
        'skrining' => 'array',
        'asesmen' => 'array',
        'identifikasi_masalah' => 'array',
        'sasaran' => 'array',
        'perencanaan' => 'array',
        'monitoring' => 'array',
        'fasilitasi' => 'array',
        'advokasi' => 'array',
        'hasil_pelayanan' => 'array',
        'terminasi' => 'array',
    ];

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kdpegsimrs');
    }
    public function petugas_updated()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kdpegsimrs_updated');
    }
}

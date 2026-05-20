<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsuhanKeperawatanPerioperatif extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'pengkajian_faktor_resiko'           => 'array',
        'pengkajian_posisi_canul_intra_vena' => 'array',
        'luaran_utama'                       => 'array',
        'luaran_hasil'                       => 'array',
        'intervensi_utama'                   => 'array',
        'intervensi_pendukung'               => 'array',
        'implementasi_observasi'             => 'array',
        'implementasi_terupetik'             => 'array',
        'implementasi_kolaborasi'            => 'array',
    ];
}

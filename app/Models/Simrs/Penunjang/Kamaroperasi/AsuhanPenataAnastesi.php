<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsuhanPenataAnastesi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'pra_masalah_kesehatan'         => 'array',
        'pra_intervensi'                => 'array',
        'pra_implementasi'              => 'array',
        'pra_evaluasi'                  => 'array',
        'intra_teknik_anestesi'         => 'array',
        'intra_posisi_operasi'          => 'array',
        'intra_masalah_kesehatan'       => 'array',
        'intra_intervensi'              => 'array',
        'intra_implementasi'            => 'array',
        'intra_evaluasi'                => 'array',
        'pasca_pernafasan_status'       => 'array',
        'pasca_masalah_kesehatan'       => 'array',
        'pasca_intervensi'              => 'array',
        'pasca_implementasi'            => 'array',
        'pasca_evaluasi'                => 'array',
    ];
}

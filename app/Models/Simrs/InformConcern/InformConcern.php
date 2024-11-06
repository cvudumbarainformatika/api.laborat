<?php

namespace App\Models\Simrs\InformConcern;

use App\Models\KunjunganRawatInap;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformConcern extends Model
{
    use HasFactory;
    protected $table = 'inform_concern';
    protected $guarded = ['id'];

    protected $casts = [
        'diagnosis' => 'array',
        'tujuan' => 'array',
        'resiko' => 'array',
        'prognosis' => 'array',
    ];

}

<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssasmentPraInduksi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'obat_pre_medikasi' => 'array'
    ];
}

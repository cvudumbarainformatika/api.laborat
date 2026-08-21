<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenilaianObatLuar extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $table = 'pelayanan_penilaian_obat_luars';
    protected $guarded = ['id'];
    protected $casts = [
        'detail' => 'array',
    ];
}

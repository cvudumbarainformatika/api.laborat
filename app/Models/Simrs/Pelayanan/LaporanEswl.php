<?php

namespace App\Models\Simrs\Pelayanan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanEswl extends Model
{
    use HasFactory;

    protected $table = 'laporan_eswls';
    protected $guarded = ['id'];

    protected $casts = [
        'batu_detail' => 'array',
        'lokalisasi_xray' => 'array',
        'lokalisasi_usg' => 'array',
        'penembakan_detail' => 'array',
    ];
}

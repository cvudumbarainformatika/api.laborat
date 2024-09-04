<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penjualan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganPenjualan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $casts = [
        'keterangan' => 'array',
    ];
}

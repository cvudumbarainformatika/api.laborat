<?php

namespace App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanSambung extends Model
{
    use HasFactory, LogsActivity;
    protected $connection = 'mysql';
    protected $table = 'rs253_sambung';
    protected $guarded = ['id'];
    protected $casts = [
        'edukasi' => 'array',
    ];


    

    
}

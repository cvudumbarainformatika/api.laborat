<?php

namespace App\Models\Simrs\Master;

use App\Models\Pegawai\Mpegawaisimpeg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsuratKeteranganDokter extends Model
{
    use HasFactory;
    protected $table = 'rs1surat';
    protected $guarded = ['id'];
}

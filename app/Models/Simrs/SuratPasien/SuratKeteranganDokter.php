<?php

namespace App\Models\Simrs\SuratPasien;

use App\Models\Pegawai\Mpegawaisimpeg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKeteranganDokter extends Model
{
    use HasFactory;
    protected $table = 'suratketerangandokter';
    protected $guarded = ['id'];
    protected $casts = [
        'kepribadian' => 'array',
        'riwayatObat' => 'array',
    ];

     public function dokter()
    {
        return $this->belongsTo(Mpegawaisimpeg::class, 'dokter', 'kdpegsimrs');
    }
}

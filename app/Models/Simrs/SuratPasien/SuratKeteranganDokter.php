<?php

namespace App\Models\Simrs\SuratPasien;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simrs\Tindakan\Tindakan;
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

    public function tindakaanbilling()
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id', 'id');
    }
}

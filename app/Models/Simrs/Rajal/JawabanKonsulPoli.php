<?php

namespace App\Models\Simrs\Rajal;

use App\Models\KunjunganPoli;
use App\Models\Simrs\Master\Mpoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanKonsulPoli extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    // protected $casts = [
    //     'pertanyaan' => 'array',
    //     'jawaban' => 'array',
    // ];
    public function poliAsal()
    {
        return $this->belongsTo(Mpoli::class, 'poli_asal', 'rs1');
    }
    public function poliTujuan()
    {
        return $this->belongsTo(Mpoli::class, 'poli_tujuan', 'rs1');
    }

    public function peminta_konsul()
    {
        return $this->belongsTo(KunjunganPoli::class, 'noreg_lama', 'rs1');
    }

     public function pemberi_konsul()
    {
        return $this->belongsTo(KunjunganPoli::class, 'noreg_baru', 'rs1');
    }
}

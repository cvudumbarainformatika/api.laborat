<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Ruangan;

use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanRetur extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $casts = [
        'depo' => 'array',
    ];

    public function rinci()
    {
        return $this->hasMany(PermintaanReturDetail::class, 'nopermintaan', 'nopermintaan');
    }
    public function pegawai()
    {
        return $this->belongsTo(Petugas::class, 'kdpegsimrs', 'kdpegsimrs');
    }
    public function pasien()
    {
        return $this->belongsTo(Mpasien::class, 'norm', 'rs1');
    }
}

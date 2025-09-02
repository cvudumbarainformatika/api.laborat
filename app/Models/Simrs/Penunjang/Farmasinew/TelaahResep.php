<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelaahResep extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
    protected $casts = [
        'administrasi' => 'array',
        'farmasi_klinis' => 'array',
        'komponen_resep' => 'array',
    ];

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'id', 'user_input');
    }
    public function pasien()
    {
        return $this->belongsTo(Mpasien::class, 'norm', 'rs1');
    }
    public function resep()
    {
        return $this->belongsTo(Resepkeluarheder::class, 'noresep', 'noresep');
    }
}

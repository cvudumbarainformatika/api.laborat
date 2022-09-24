<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganPoli extends Model
{
    use HasFactory;

    protected $table = 'rs17';

    public function transaksi_laborat()
    {
        return $this->hasMany(TransaksiLaborat::class, 'rs1', 'rs1');
    }
    public function sistem_bayar()
    {
        return $this->belongsTo(SistemBayar::class, 'rs14', 'rs1');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'rs2', 'rs1');
    }
}

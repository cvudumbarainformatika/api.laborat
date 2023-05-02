<?php

namespace App\Models\Simrs\Maping;

use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mruangan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mminmaxobat extends Model
{
    use HasFactory;
    protected $table = 'min_max_ruang';
    protected $guarded = ['id'];

    public function obat()
    {
        return $this->belongsTo(Mobat::class, 'kd_obat', 'rs1');
    }

    public function ruanganx()
    {
        return $this->belongsTo(Mruangan::class, 'kd_ruang', 'kode');
    }

}

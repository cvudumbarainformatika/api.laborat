<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaxRuangan extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';

    protected $guarded = ['id'];

    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
    }
}

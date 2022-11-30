<?php

namespace App\Models\Sigarang\Transaksi;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'kode_gudang', 'kode');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }
}

<?php

namespace App\Models\Sigarang\Transaksi\Permintaanruangan;

use App\Models\Sigarang\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaanruangan extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];


    public function details()
    {
        return $this->hasMany(DetailPermintaanruangan::class);
    }

    public function pj()
    {
        return $this->belongsTo(Pengguna::class, 'kode_penanggungjawab', 'kode');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }
}

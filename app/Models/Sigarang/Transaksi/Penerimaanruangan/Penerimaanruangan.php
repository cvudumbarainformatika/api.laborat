<?php

namespace App\Models\Sigarang\Transaksi\Penerimaanruangan;

use App\Models\Sigarang\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penerimaanruangan extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(DetailsPenerimaanruangan::class);
    }

    public function pj()
    {
        return $this->belongsTo(Pengguna::class, 'kode_penanggungjawab', 'kode');
    }
}

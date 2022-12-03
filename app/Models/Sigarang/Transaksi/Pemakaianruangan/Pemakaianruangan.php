<?php

namespace App\Models\Sigarang\Transaksi\Pemakaianruangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemakaianruangan extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(DetailsPemakaianruangan::class);
    }

    public function pj()
    {
        return $this->belongsTo(Pengguna::class, 'kode_penanggungjawab', 'kode');
    }
}

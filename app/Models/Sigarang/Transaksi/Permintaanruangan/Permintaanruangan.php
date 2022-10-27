<?php

namespace App\Models\Sigarang\Transaksi\Permintaanruangan;

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
}

<?php

namespace App\Models\Sigarang\Transaksi\Permintaanruangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemintaanruangan extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $connection = 'sigarang';

    public function details()
    {
        return $this->hasMany(DetailPemintaanruangan::class);
    }
}

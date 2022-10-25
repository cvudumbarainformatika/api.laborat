<?php

namespace App\Models\Sigarang\Transaksi\Permintaanruangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPemintaanruangan extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function permintaanruangan()
    {
        return $this->belongsTo(Permintaanruangan::class);
    }
}

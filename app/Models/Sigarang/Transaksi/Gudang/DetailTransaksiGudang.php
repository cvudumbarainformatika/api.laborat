<?php

namespace App\Models\Sigarang\Transaksi\Gudang;

use App\Models\Sigarang\Barang108;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Satuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaksiGudang extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];
    public function barang108()
    {
        return $this->belongsTo(Barang108::class, 'kode_108', 'kode');
        // return $this->belongsTo(Barang108::class, 'kode', 'kode_108');
    }

    public function barangrs()
    {
        return  $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function satuan()
    {
        return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function transaction()
    {
        return $this->belongsTo(TransaksiGudang::class);
    }
}

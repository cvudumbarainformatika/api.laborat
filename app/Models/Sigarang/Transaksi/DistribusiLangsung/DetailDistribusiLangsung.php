<?php

namespace App\Models\Sigarang\Transaksi\DistribusiLangsung;

use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Satuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailDistribusiLangsung extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
    }

    public function distribusi()
    {
        return $this->belongsTo(DistribusiLangsung::class);
    }
}

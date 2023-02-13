<?php

namespace App\Models\Sigarang\Transaksi\Pemesanan;

use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemesanan extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function perusahaan()
    {
        return  $this->belongsTo(Supplier::class, 'kode_perusahaan', 'kode');
        // return $this->belongsTo(Satuan::class, 'kode', 'kode_satuan');
    }
    public function details()
    {
        return $this->hasMany(DetailPemesanan::class);
    }

    public function details_kontrak()
    {
        return $this->belongsTo(KontrakPengerjaan::class, 'kontrak', 'nokontrakx');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nomor', 'LIKE', '%' . $query . '%')
                ->orWhere('tanggal', 'LIKE', '%' . $query . '%')
                ->orWhere('kontrak', 'LIKE', '%' . $query . '%');

            // ->orWhereHas('barangrs', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // })->orWhereHas('satuan', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // });
        });
    }
}

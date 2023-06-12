<?php

namespace App\Models\Sigarang\Transaksi\Penerimaan;

use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Supplier;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penerimaan extends Model
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
        return $this->hasMany(DetailPenerimaan::class);
    }

    public function pemesanan()
    {
        return $this->hasOne(Pemesanan::class, 'nomor', 'nomor');
    }

    public function details_kontrak()
    {
        return $this->belongsTo(KontrakPengerjaan::class, 'kontrak', 'nokontrak');
    }
    public function stokgudang()
    {
        return $this->hasMany(RecentStokUpdate::class, 'no_penerimaan', 'no_penerimaan');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nomor', 'LIKE', '%' . $query . '%')
                ->orWhere('tanggal', 'LIKE', '%' . $query . '%')
                ->orWhere('pengirim', 'LIKE', '%' . $query . '%')
                ->orWhere('kontrak', 'LIKE', '%' . $query . '%');
            // ->orWhereHas('barangrs', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // })->orWhereHas('satuan', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // });
        });
        $search->when($reqs['r'] ?? false, function ($search, $query) {
            $ruang = Supplier::select('kode')->where('nama', 'LIKE', '%' . $query . '%')->get();
            return $search->whereIn('kode_perusahaan', $ruang);
        });
    }
}

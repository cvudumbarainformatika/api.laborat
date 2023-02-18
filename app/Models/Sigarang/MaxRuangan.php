<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaxRuangan extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';

    protected $guarded = ['id'];

    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
    }
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['barang'] || $reqs['ruang'] ?? false, function ($search, $query) use ($reqs) {
            $barang = $reqs['barang'] ? $reqs['barang'] : '';
            $pengguna = $reqs['ruang'] ? $reqs['ruang'] : '';
            $search->hasByNonDependentSubquery('barang', function ($q) use ($barang) {
                $q->where('nama', 'like', '%' . $barang . '%');
            })->hasByNonDependentSubquery('ruang', function ($q) use ($pengguna) {
                $q->where('uraian', 'like', '%' . $pengguna . '%');
            });
            // return $search->whereHas('barang', function ($q) use ($barang) {
            //     $q->where('nama', 'like', '%' . $barang . '%');
            // })->whereHas('ruang', function ($q) use ($pengguna) {
            //     $q->where('uraian', 'like', '%' . $pengguna . '%');
            // });
        });
    }
}

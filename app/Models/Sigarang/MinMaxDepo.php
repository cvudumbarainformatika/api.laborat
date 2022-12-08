<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinMaxDepo extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];


    public function depo()
    {
        return $this->belongsTo(Gudang::class, 'kode_depo', 'kode');
    }

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->whereHas('barang', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            })->OrWhereHas('depo', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            });
            // return $search->where('uraian', 'LIKE', '%' . $query . '%')
            //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
        $search->when($reqs['barang'] ?? false, function ($search, $query) {
            return $search->whereHas('barang', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            });
            // return $search->where('uraian', 'LIKE', '%' . $query . '%')
            //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
        $search->when($reqs['depo'] ?? false, function ($search, $query) {
            return $search->whereHas('depo', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            });
            // return $search->where('uraian', 'LIKE', '%' . $query . '%')
            //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}

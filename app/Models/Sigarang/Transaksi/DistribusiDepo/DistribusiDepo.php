<?php

namespace App\Models\Sigarang\Transaksi\DistribusiDepo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribusiDepo extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(DetailDistribusiDepo::class);
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('no_distribusi', 'LIKE', '%' . $query . '%');
            // ->orWhere('tanggal', 'LIKE', '%' . $query . '%');

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

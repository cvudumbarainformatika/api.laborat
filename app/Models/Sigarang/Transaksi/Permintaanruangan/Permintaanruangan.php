<?php

namespace App\Models\Sigarang\Transaksi\Permintaanruangan;

use App\Models\Sigarang\Pengguna;
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

    public function pj()
    {
        return $this->belongsTo(Pengguna::class, 'kode_penanggungjawab', 'kode');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('no_permintaan', 'LIKE', '%' . $query . '%');
            // ->orWhere('tanggal', 'LIKE', '%' . $query . '%')
            // ->orWhere('kontrak', 'LIKE', '%' . $query . '%');

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

<?php

namespace App\Models\Sigarang\Transaksi\Permintaanruangan;

use App\Models\Sigarang\Pengguna;
use App\Models\Sigarang\Ruang;
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

    public function ruangan()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('no_permintaan', 'LIKE', '%' . $query . '%');
        });
        $search->when($reqs['r'] ?? false, function ($search, $query) {
            $ruang = Ruang::select('kode')->where('uraian', 'LIKE', '%' . $query . '%')->get();
            return $search->whereIn('kode_ruang', $ruang);
        });
    }
}
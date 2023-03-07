<?php

namespace App\Models\Sigarang\Transaksi\DistribusiLangsung;

use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Ruang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribusiLangsung extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_depo', 'kode');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function detailLangsung()
    {
        return $this->hasMany(DetailDistribusiLangsung::class, 'distribusi_langsung_id');
    }
}

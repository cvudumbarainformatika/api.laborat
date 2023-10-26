<?php

namespace App\Models\Sigarang\Transaksi\Permintaanruangan;

use App\Models\Sigarang\Barang108;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\MaxRuangan;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Ruang;
use App\Models\Sigarang\Satuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPermintaanruangan extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function gudang()
    {
        return  $this->belongsTo(Gudang::class, 'dari', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }
    public function ruang()
    {
        return  $this->belongsTo(Ruang::class, 'tujuan', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }
    public function satuan()
    {
        return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function barangrs()
    {
        return  $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }
    public function barang108()
    {
        return  $this->belongsTo(Barang108::class, 'kode_rs', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function permintaanruangan()
    {
        return $this->belongsTo(Permintaanruangan::class);
    }
    public function sisastok()
    {
        return $this->hasMany(RecentStokUpdate::class, 'kode_rs', 'kode_rs');
    }

    public function getStokRuanganAttribute()
    {
        $kode_ruangan = $this->tujuan;
        $kode_rs = $this->kode_rs;
        $data = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_ruangan)->sum('sisa_stok');
        return $data;
    }
    public function getMaxStokAttribute()
    {
        $kode_ruangan = $this->tujuan;
        $kode_rs = $this->kode_rs;
        $data = MaxRuangan::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_ruangan)
            ->first();
        return $data ? $data->max_stok : 0;
    }
}

<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyStokUpdate extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function penyesuaian()
    {
        return $this->hasOne(StokOpname::class);
    }

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
    }
    public function depo()
    {
        return $this->belongsTo(Gudang::class, 'kode_ruang', 'kode');
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_ruang', 'kode');
    }
}

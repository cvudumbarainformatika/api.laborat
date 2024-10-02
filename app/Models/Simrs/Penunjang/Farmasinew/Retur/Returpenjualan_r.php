<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returpenjualan_r extends Model
{
    use HasFactory;
    protected $table = 'retur_penjualan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function mobatnew()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
    public function header()
    {
        return $this->hasOne(Resepkeluarheder::class, 'noresep', 'noresep');
    }
    public function rincipenerimaan()
    {
        return $this->hasMany(PenerimaanRinci::class, 'kdobat', 'kdobat');
    }
    public function opname()
    {
        return $this->hasMany(Stokopname::class, 'kdobat', 'kdobat');
    }
}

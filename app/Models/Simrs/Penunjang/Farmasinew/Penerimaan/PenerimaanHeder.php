<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenerimaanHeder extends Model
{
    use HasFactory;
    protected $table = 'penerimaan_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function penerimaanrinci()
    {
        return $this->hasMany(PenerimaanRinci::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function bastr()
    {
        return $this->hasMany(BastrinciM::class, 'nopenerimaan', 'nopenerimaan');
    }

    public function pihakketiga()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kdpbf');
    }
    public function gudang()
    {
        return $this->hasOne(Gudang::class, 'kode', 'gudang');
    }
    public function faktur()
    {
        return $this->hasOne(Faktur::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function retur()
    {
        return $this->hasMany(Returpbfheder::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function rincibast()
    {
        return $this->hasMany(BastrinciM::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function rincianbast()
    {
        return $this->hasMany(BastrinciM::class, 'nobast', 'nobast');
    }
    public function terima()
    {
        return $this->belongsTo(Pegawai::class, 'user', 'kdpegsimrs');
    }
    public function bast()
    {
        return $this->belongsTo(Pegawai::class, 'user_bast', 'kdpegsimrs');
    }
    public function bayar()
    {
        return $this->belongsTo(Pegawai::class, 'user_bayar', 'kdpegsimrs');
    }

    public function penjualanrinci()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'nopenerimaan', 'nopenerimaan');
    }
}

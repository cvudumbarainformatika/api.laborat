<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Sigarang\Gudang;
use App\Models\Simrs\Master\Mpihakketiga;
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
}

<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Models\Simrs\Master\Mpihakketiga;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemesananHeder extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'pemesanan_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function pihakketiga()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kdpbf');
    }

    public function rinci()
    {
        return $this->hasMany(PemesananRinci::class, 'nopemesanan', 'nopemesanan');
    }
}

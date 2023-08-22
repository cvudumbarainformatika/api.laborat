<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemesananRinci extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'pemesanan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function masterobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
}

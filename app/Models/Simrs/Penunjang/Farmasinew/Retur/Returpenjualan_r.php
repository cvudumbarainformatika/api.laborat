<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
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
}

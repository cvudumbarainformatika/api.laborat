<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RencanabeliR extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'perencana_pebelian_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rincian()
    {
        return $this->hasOne(RencanabeliH::class, 'no_rencbeliobat', 'no_rencbeliobat');
    }

    public function mobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
}

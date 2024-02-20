<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersiapanOperasiRinci extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';


    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanHeder::class, 'nopenerimaan', 'nopenerimaan');
    }
}

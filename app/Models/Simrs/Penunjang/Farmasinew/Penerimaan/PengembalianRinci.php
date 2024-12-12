<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengembalianRinci extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function header()
    {
        return $this->belongsTo(Pengembalian::class, 'nopengembalian', 'nopengembalian');
    }
    public function rinci_penerimaan()
    {
        return $this->belongsTo(PenerimaanRinci::class, 'id_rincipenerimaan');
    }
    public function masterobat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kdobat');
    }
}

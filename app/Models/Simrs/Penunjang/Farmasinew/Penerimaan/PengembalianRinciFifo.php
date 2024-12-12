<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengembalianRinciFifo extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function header()
    {
        return $this->belongsTo(Pengembalian::class, 'nopengembalian', 'nopengembalian');
    }
    public function rinci()
    {
        return $this->belongsTo(Pengembalian::class, 'nopengembalian', 'nopengembalian');
    }
}

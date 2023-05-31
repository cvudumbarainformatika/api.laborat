<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use App\Models\Simrs\Master\Mobat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekrajalracikanrincilalu extends Model
{
    use HasFactory;
    protected $table = 'rs164';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';
    protected $appends = ['subtotal'];

    public function racikanrinci()
    {
        return $this->belongsTo(Mobat::class, 'rs4', 'rs1');
    }

    public function getSubtotalAttribute()
    {
        $harga = (int) $this->rs7;
        $jumlah = (int) $this->rs5;
        return ($harga*$jumlah);
    }
}

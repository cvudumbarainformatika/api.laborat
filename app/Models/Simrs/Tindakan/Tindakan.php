<?php

namespace App\Models\Simrs\Tindakan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tindakan extends Model
{
    use HasFactory;
    protected $table = 'rs73';
    protected $guarded = [''];
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $harga1 = $this->rs7 ? $this->rs7 : 0;
        $harga2 = $this->rs13 ? $this->rs13 : 0;
        $jumlah = $this->rs5 ? $this->rs5 : 1;
        $subtotal = ($harga1+$harga2)*$jumlah;
        return ($subtotal);
    }

}

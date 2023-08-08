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
        $harga1 = (int) $this->rs7 ? $this->rs7 : 0;
        $harga2 = (int)  $this->rs13 ? $this->rs13 : 0;
        $jumlah = (int) $this->rs5 ? $this->rs5 : 1;

        $hargatotal = $harga1 + $harga2;
        $subtotal = $hargatotal*$jumlah;
       //$subtotal = ($harga1+$harga2)*$jumlah;
        return ($subtotal);
    }

}

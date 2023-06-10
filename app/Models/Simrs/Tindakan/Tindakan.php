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
        $harga1 = $this->rs7;
        $harga2 = $this->rs13;
        $jumlah = $this->rs5;
        $subtotal = ($harga1+$harga2)*$jumlah;
        return ($subtotal);
    }

}

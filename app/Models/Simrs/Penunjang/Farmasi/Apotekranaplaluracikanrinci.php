<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekranaplaluracikanrinci extends Model
{
    use HasFactory;
    protected $table = 'rs64';
    protected $guarded = ['id'];

    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $harga1 = $this->rs5;
        $harga2 = $this->rs7;
        $subtotal = ($harga1*$harga2);
        return ($subtotal);
    }
}

<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mwilayah extends Model
{
    use HasFactory;
    protected $table = 'wilayah';
    protected $guarded = ['id'];


    public function getKodeFullAttribute()
    {
        return
            $this->kode2 .
            str_pad($this->kode3, 2, '0', STR_PAD_LEFT) .
            str_pad($this->kode4, 2, '0', STR_PAD_LEFT) .
            str_pad($this->kode5, 4, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanLaborat extends Model
{
    use HasFactory;

    protected $table='rs49';
    protected $guarded = ['id'];

    public function transaksi_laborat()
    {
        return $this->hasMany(TransaksiLaborat::class, 'rs1');
    }

    // public function paket_laborats(){
    //     return $this->hasMany(self::class, 'rs21');
    // }

}

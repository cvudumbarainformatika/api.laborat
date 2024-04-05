<?php

namespace App\Models\Simrs\Pelayanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeonatusMedis extends Model
{
    use HasFactory;
    protected $table = 'neonatusmedis';
    protected $guarded = ['id'];

    public function riwayatkehamilan()
    {
       return $this->hasMany(RiwayatKehamilan::class, 'norm','norm');
    } 
}

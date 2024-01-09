<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resepkeluarrinci extends Model
{
    use HasFactory;
    protected $table = 'resep_keluar_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function mobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }

    public function heder()
    {
        return $this->hasOne(Resepkeluarheder::class, 'noresep', 'noresep');
    }
}

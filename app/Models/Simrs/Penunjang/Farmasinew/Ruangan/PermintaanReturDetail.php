<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Ruangan;

use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanReturDetail extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';


    public function mobat()
    {
        return $this->belongsTo(Mobatnew::class, 'kdobat', 'kd_obat');
    }
    public function headerResep()
    {
        return $this->hasone(Resepkeluarheder::class, 'noresep', 'noresep');
    }
}

<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resepkeluarrinciracikan extends Model
{
    use HasFactory;
    protected $table = 'resep_keluar_racikan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function mobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
    public function stok()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kdobat');
    }
    public function header()
    {
        return $this->belongsTo(Resepkeluarheder::class, 'noresep', 'noresep');
    }
}

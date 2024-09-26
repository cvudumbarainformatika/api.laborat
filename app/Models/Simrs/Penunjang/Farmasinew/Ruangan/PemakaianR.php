<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Ruangan;

use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemakaianR extends Model
{
    use HasFactory;
    protected $table = 'pemakaian_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
    public function ruangan()
    {
        return $this->belongsTo(Ruang::class, 'kdruang', 'kode');
    }
}

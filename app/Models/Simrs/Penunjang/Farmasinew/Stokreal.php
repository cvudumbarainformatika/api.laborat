<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MinmaxobatController;
use App\Models\Sigarang\Gudang;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stokreal extends Model
{
    use HasFactory;
    protected $table = 'stokreal';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function minmax()
    {
        return $this->hasOne(Mminmaxobat::class, 'kd_obat', 'kdobat');
    }

    public function gudangdepo()
    {
        return $this->hasOne(Gudang::class, 'kode', 'kdruang');
    }

    public function transnonracikan()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kdobat');
    }
}

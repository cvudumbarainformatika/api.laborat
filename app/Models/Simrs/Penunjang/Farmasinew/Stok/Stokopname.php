<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Stok;

<<<<<<< HEAD
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
=======
>>>>>>> 13b8704a5e960cb39cf0516b65f95c8f3cd7a761
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stokopname extends Model
{
    use HasFactory;
    protected $table = 'stokopname';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
<<<<<<< HEAD

    public function masterobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
=======
>>>>>>> 13b8704a5e960cb39cf0516b65f95c8f3cd7a761
}

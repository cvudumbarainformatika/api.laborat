<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembacaanradiologiController extends Model
{
    use HasFactory;
    protected $table = 'rs151';
    protected $guarded = ['id'];

    public function dokterRadiologi()
    {
        return $this->hasOne(Petugas::class, 'nama', 'rs4')
            ->where('kdgroupnakes', '1')
            ->where('aktif', 'AKTIF');
    }
}

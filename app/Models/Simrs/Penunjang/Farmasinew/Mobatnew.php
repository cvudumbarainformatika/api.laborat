<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mobatnew extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'new_masterobat';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function scopeMobat($data)
    {
        return $data->select([
            'kd_obat as kodeobat',
            'nama_obat as namaobat'
        ]);
    }

    public function scopeFilter($cari, array $reqs)
    {
        $cari->when(
            $reqs['q'] ?? false,
            function ($data, $query) {
                return $data->where('kd_obat', 'LIKE', '%' . $query . '%')
                    ->orWhere('nama_obat', 'LIKE', '%' . $query . '%')
                    ->orderBy('nama_obat');
            }
        );
    }

    public function mkelasterapi()
    {
        return $this->hasMany(Mapingkelasterapi::class, 'kd_obat', 'kd_obat');
    }

    public function stokrealgudang()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokrealallrs()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kd_obat');
    }

    public function stokmaxrs()
    {
        return $this->hasMany(Mminmaxobat::class, 'kd_obat', 'kd_obat');
    }

    public function perencanaanrinci()
    {
        return $this->hasMany(RencanabeliR::class, 'kdobat', 'kd_obat');
    }
}

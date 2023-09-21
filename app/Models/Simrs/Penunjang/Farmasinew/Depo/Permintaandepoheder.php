<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaandepoheder extends Model
{
    use HasFactory;
    protected $table = 'permintaan_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function permintaanrinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'no_permintaan', 'no_permintaan');
    }

    public function user()
    {
        return $this->hasOne(Pegawai::class, 'id', 'user');
    }
}

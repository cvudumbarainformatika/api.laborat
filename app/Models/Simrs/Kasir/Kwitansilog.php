<?php

namespace App\Models\Simrs\Kasir;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kwitansilog extends Model
{
    use HasFactory;
    protected $table = 'kwitansilog';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $connection = 'mysql';

    public function rincian()
    {
        return $this->hasMany(Kwitansidetail::class, 'no_kwitansi','nokwitansi');
    }
    public function pegawai()
    {
        return $this->hasOne(Pegawai::class, 'kdpegsimrs','userid');
    }
}

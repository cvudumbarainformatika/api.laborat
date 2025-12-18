<?php

namespace App\Models\Simrs\Kasir;

use App\Models\Pegawai\Mpegawaisimpeg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tbp extends Model
{
    use HasFactory;
    protected $table = 'tbp';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function kwitansi()
    {
        return $this->hasMany(Kwitansilog::class, 'no_tbp', 'no_tbp');
    }

    public function pegawai()
    {
        return $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'penyetor');
    }
}

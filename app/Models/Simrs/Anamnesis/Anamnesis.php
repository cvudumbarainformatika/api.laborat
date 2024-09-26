<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anamnesis extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs209';
    protected $guarded = ['id'];
    protected $casts = [
        'riwayatalergi' => 'array',
      ];


    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'user');
    }

    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs','user');
    }

    public function keluhannyeri()
    {
        return $this->hasOne(KeluhanNyeri::class, 'rs209_id', 'id');
    }
}

<?php

namespace App\Models\Pegawai;

use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Ruang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }
    public function kategory()
    {
        return $this->belongsTo(Kategory::class);
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class);
    }
}

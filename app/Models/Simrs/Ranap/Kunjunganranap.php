<?php

namespace App\Models\Simrs\Ranap;

use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Master\Dokter;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mruangan;
use App\Models\Simrs\Master\Msistembayar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjunganranap extends Model
{
    use HasFactory;
    protected $table = 'rs23';
    protected $gurded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';

    public function relmasterruangranap()
    {
        return $this->hasOne(Mruangranap::class, 'rs1', 'rs5');
    }

    public function relsistembayar()
    {
        return $this->hasOne(Msistembayar::class, 'rs1', 'rs19');
    }

    public function reldokter()
    {
        return $this->hasOne(Dokter::class, 'rs1', 'rs10');
    }

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    public function rstigalimax()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1');
    }
    public function rstigalimaxx()
    {
        return $this->hasMany(Rstigalimax::class, 'rs1', 'rs1')->take(2);
    }

}

<?php

namespace App\Models\Simrs\Ranap;

use App\Models\Simrs\Master\Dokter;
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
        return $this->hasMany(Mruangranap::class, 'rs1', 'rs5');
    }

    public function relsistembayar()
    {
        return $this->hasMany(Msistembayar::class, 'rs1', 'rs19');
    }

    public function reldokter()
    {
        return $this->hasMany(Dokter::class, 'rs1', 'rs10');
    }

}

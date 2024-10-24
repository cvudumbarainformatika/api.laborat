<?php

namespace App\Models\Simrs\Konsultasi;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Konsultasi extends Model
{
    use HasFactory;
    protected $table = 'konsultasi_rs140';
    protected $guarded = ['id'];
    protected $connection = 'mysql';

    public function tarif()
    {
        return $this->hasOne(Visite::class, 'rs1', 'noreg');
    }

    
}

<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Simpeg\Petugas;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PascaSedasi extends Model
{
    use HasFactory, LogsActivity;

    protected $connection = 'mysql';
    protected $table = 'pasca_sedasi';
    protected $guarded = ['id'];

    public function dokter_anestesi()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kddokter');
    }

    public function operator_rel()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kd_operator');
    }

    public function asisten_rel()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kd_asisten');
    }
}

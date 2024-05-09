<?php

namespace App\Models\Simrs\Pelayanan\Diagnosa;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Pelayanan\Intervensikeperawatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosakeperawatan extends Model
{
    use HasFactory;
    protected $table = 'diagnosakeperawatan';
    protected $guarded = ['id'];
    // protected $connection = 'kepex';

    public function intervensi()
    {
        return $this->hasMany(Intervensikeperawatan::class, 'diagnosakeperawatan_kode', 'id');
    }

    public function masterperawat()
    {
        return $this->hasOne(Pegawai::class, 'id', 'user_input');
    }
}

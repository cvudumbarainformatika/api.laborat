<?php

namespace App\Models\Simrs\Pelayanan\Diagnosa;

use App\Models\Simrs\Pelayanan\Intervensikeperawatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosakeperawatan extends Model
{
    use HasFactory;
    protected $table = 'diagnosakeperawatan';
    protected $guarded = ['id'];

    public function intervensi()
    {
        return $this->hasMany(Intervensikeperawatan::class, 'diagnosakeperawatan_kode', 'id');
    }
}

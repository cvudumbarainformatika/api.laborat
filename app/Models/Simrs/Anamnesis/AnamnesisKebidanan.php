<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnamnesisKebidanan extends Model
{
    use HasFactory;
    protected $table = 'anamnesis_kebidanan_igd';
    protected $guarded = ['id'];


    public function pegawai()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'userentry');
    }
}

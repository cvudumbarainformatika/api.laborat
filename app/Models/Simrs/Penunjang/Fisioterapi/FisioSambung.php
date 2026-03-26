<?php

namespace App\Models\Simrs\Penunjang\Fisioterapi;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FisioSambung extends Model
{
  use HasFactory;
  protected $table = 'rs201_sambung';
  protected $guarded = ['id'];

  // protected $casts = [
  //   'diagnosis_fungsional' => 'array',
  //   'problem_rehabilitasimedik' => 'array',
  // ];

  // public function petugas()
  // {
  //   return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'user');
  // }
}

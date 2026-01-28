<?php

namespace App\Models\Simrs\Penunjang\Fisioterapi;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FisioSoap extends Model
{
  use HasFactory;
  protected $table = 'fisio_soap';
  protected $guarded = ['id'];

  // protected $casts = [
  //   'diagnosis_fungsional' => 'array',
  //   'problem_rehabilitasimedik' => 'array',
  // ];

  public function petugas()
  {
    return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'user');
  }
}

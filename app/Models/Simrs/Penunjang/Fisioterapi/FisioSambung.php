<?php

namespace App\Models\Simrs\Penunjang\Fisioterapi;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Tindakan\Tindakan;
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

  public function tindakan()
  {
    return $this->hasMany(Tindakan::class, 'rs1', 'noreg');
  }

  public function soap()
  {
    return $this->hasMany(FisioSoap::class, 'noreg', 'link_noreg');
  }
}

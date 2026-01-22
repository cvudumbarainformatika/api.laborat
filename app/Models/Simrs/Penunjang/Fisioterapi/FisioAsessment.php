<?php

namespace App\Models\Simrs\Penunjang\Fisioterapi;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\PembacaanradiologiController;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FisioAsessment extends Model
{
  use HasFactory;
  protected $table = 'fisio_asesmen';
  protected $guarded = ['id'];

  protected $casts = [
    'diagnosis_fungsional' => 'array',
    'problem_rehabilitasimedik' => 'array',
  ];

  public function petugas()
  {
    return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'user');
  }
}

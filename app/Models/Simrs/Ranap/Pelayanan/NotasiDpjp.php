<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotasiDpjp extends Model
{
  use HasFactory;
  protected $connection = 'mysql';
  protected $table = 'cppt_notasi';
  protected $guarded = ['id'];

  public function petugas()
  {
    return $this->hasOne(Petugas::class, 'kdpegsimrs', 'user');
  }
}

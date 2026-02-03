<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MFisioFrekuensi extends Model
{
  use HasFactory;
  protected $table = 'fisio_master_frek';
  protected $guarded = ['id'];
}

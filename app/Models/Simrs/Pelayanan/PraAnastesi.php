<?php

namespace App\Models\Simrs\Pelayanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PraAnastesi extends Model
{
    use HasFactory;
    protected $table = 'pra_anastesi';
    protected $guarded = ['id'];
    protected $casts = [
      'asaClasification' => 'array',
      'kajianSistem' => 'array',
      'laboratorium' => 'array',
      'laboratorium' => 'array',
      'penyulitAnastesi' => 'array',
  ];
}

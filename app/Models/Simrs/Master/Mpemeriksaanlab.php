<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mpemeriksaanlab extends Model
{
   use HasFactory, SoftDeletes;
   protected $table = 'rs49';
   protected $guarded = ['id'];


   public function spesimen()
   {
      return $this->hasOne(MspesimenLab::class, 'rs1', 'rs1');
   }

   public function loinclab()
   {
      return $this->hasMany(MloincLab::class, 'code', 'loinc');
   }
}

<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Ews\MapingProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mtindakan extends Model
{
   use HasFactory, SoftDeletes;
   protected $table = 'rs30';
   protected $guarded = ['idx'];
   public $primaryKey = 'idx';
   // public $primarykey = 'rs1';
   // protected $keyType = 'string';

   public function maapingprocedure()
   {
      return $this->hasOne(MapingProcedure::class, 'kdMaster', 'rs1');
   }

   public function snowmed()
   {
      return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'kode');
   }
   public function snowmedx()
   {
      return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'rs1');
   }

   public function icd()
   {
      return $this->hasOne(Icd9prosedure::class, 'kd_prosedur', 'icd9');
   }
}

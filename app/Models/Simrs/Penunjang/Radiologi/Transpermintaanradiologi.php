<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transpermintaanradiologi extends Model
{
    use HasFactory;
    protected $table = 'rs106';
    protected $guarded = ['id'];
    // public $timestamps = false;
    // protected $primaryKey = 'rs1';
    //   protected $keyType = 'string';

    public function reltransrinci()
    {
        return  $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }
}

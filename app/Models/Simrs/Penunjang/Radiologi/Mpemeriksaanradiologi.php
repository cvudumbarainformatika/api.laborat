<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use App\Models\Simrs\Master\MappingSnowmed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mpemeriksaanradiologi extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'rs47';
    protected $guarded = ['id1'];
    protected $primaryKey = 'id1';
    // public $timestamps = false;


    public function snowmed()
    {
        return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'rs1');
    }
}

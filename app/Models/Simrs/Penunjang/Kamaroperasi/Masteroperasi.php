<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Simrs\Master\MappingSnowmed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Masteroperasi extends Model
{
    use HasFactory,  SoftDeletes;
    protected $table = 'rs53';
    // protected $guarded = ['id'];
    protected $guarded = ['idx'];
    public $primaryKey = 'idx';

    public function snowmed()
    {
        return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'rs1');
    }
}

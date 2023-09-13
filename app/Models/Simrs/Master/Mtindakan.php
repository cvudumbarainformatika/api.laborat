<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Ews\MapingProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mtindakan extends Model
{
    use HasFactory;
    protected $table = 'rs30';
    protected $guarded = ['id'];

    public function maapingprocedure()
    {
        return $this->hasOne(MapingProcedure::class, 'kdMaster', 'rs1');
    }
}

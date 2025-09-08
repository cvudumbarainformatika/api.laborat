<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapRincian extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'kelompokMap_R';
    protected $guarded = ['id'];

    public function dataarsip()
    {
        return $this->belongsTo(Dataarsip::class, 'noarsip', 'noarsip');
    }
}

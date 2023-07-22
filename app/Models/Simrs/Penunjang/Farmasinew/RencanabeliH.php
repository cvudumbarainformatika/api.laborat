<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RencanabeliH extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'perencana_pebelian_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rincian()
    {
        return $this->hasMany(RencanabeliR::class, 'no_rencbeliobat', 'no_rencbeliobat');
    }
}

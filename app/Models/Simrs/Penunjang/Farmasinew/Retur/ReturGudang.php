<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturGudang extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function details()
    {
        return $this->hasMany(ReturGudangDetail::class, 'no_retur', 'no_retur');
    }
}

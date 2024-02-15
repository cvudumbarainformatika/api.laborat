<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Ruangan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemakaianH extends Model
{
    use HasFactory;
    protected $table = 'pemakaian_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rinci()
    {
        return $this->hasMany(PemakaianR::class, 'nopemakaian', 'nopemakaian');
    }
}

<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returpbfheder extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $table = 'retur_penyedia_h';
    protected $guarded = ['id'];

    public function rinci()
    {
        return $this->hasMany(Returpbfrinci::class, 'no_retur', 'no_retur');
    }
}

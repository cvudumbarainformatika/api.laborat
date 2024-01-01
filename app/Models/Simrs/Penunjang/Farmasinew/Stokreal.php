<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MinmaxobatController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stokreal extends Model
{
    use HasFactory;
    protected $table = 'stokreal';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function minmax()
    {
        return $this->hasOne(Mminmaxobat::class, 'kd_obat', 'kdobat');
    }
}

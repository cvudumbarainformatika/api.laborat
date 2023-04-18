<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mobat extends Model
{
    use HasFactory;
    protected $table = 'rs32';
    protected $guarded = ['rs1'];

    public function scopeMobat($data)
    {
        return $data->select([
            'rs1 as kodeobat',
            'rs2 as namaobat'
        ]);
    }


}



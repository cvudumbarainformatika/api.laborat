<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transpermintaanradiologi extends Model
{
    use HasFactory;
    protected $table = 'rs106';
    protected $gurded = ['id'];
    public $timestamps = false;
    protected $primaryKey = 'rs1';

    public function reltransrinci()
    {
        return  $this->belongsTo(Transradiologi::class, 'rs1','rs1');
    }
}

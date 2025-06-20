<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RadiologiLuar extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'rs270';
    protected $guarded = ['id'];
    public $timestamps = false;


    public function rincians()
    {
       return $this->hasMany(RincianPermintaanLuar::class, 'rs1', 'rs1');
    }


}

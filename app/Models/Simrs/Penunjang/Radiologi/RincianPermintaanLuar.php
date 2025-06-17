<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RincianPermintaanLuar extends Model
{
    use HasFactory;
    protected $table = 'rs271';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function mpemeriksaanradiologi()
    {
        return $this->belongsTo(Mpemeriksaanradiologi::class, 'rs3', 'rs1');
    }

}

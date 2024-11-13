<?php

namespace App\Models\Simrs\Planing;

use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planing_Igd_ranap extends Model
{
    use HasFactory;
    protected $table = 'plann_igd_ranap';
    protected $guarded = ['id'];

    public function ruangranap()
    {
        return $this->hasOne(Mruangranap::class, 'rs1', 'ruangtujuan');
    }
}

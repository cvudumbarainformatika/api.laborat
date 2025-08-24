<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapHeder extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'kelompokMap_H';
    protected $guarded = ['id'];
}

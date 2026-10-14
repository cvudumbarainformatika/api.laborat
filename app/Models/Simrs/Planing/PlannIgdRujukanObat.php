<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlannIgdRujukanObat extends Model
{
    use HasFactory;

    protected $table = 'plann_igd_rujukan_obat';
    protected $guarded = ['id'];
}

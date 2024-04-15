<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangRusak extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $guarded = ['id'];
}

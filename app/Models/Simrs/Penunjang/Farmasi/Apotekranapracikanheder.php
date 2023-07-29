<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekranapracikanheder extends Model
{
    use HasFactory;
    protected $table = 'rs39';
    protected $guarded = ['id'];
}

<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Stok;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stokopname extends Model
{
    use HasFactory;
    protected $table = 'stokopname';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

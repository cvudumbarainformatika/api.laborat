<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Stok;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutupOpname extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}
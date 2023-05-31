<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpemeriksaanlab extends Model
{
    use HasFactory;
    protected $table = 'rs49';
    protected $guarded = ['id'];
    public $timestamps = false;
}

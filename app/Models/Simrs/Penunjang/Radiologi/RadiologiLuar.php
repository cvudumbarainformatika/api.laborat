<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiologiLuar extends Model
{
    use HasFactory;
    protected $table = 'rs270';
    protected $guarded = ['id'];
    public $timestamps = false;


}

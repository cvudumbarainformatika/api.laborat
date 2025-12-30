<?php

namespace App\Models\Siasik\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Master_Jasa extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $table = 'masterJasalain';
    
    protected $guarded = ['id'];
}

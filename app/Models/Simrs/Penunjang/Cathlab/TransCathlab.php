<?php

namespace App\Models\Simrs\Penunjang\Cathlab;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransCathlab extends Model
{
    use HasFactory;
    protected $table = 'cathlab';
    protected $guarded = ['id'];
}

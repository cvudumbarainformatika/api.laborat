<?php

namespace App\Models\Spo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mspo extends Model
{
    use HasFactory;
    protected $table = 'sop2';
    protected $connection = 'spo';
    protected $guarded = ['id'];
    public $timestamps = false;
}

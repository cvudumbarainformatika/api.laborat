<?php

namespace App\Models\Siasik\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akun_mapjurnal extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'akun_mapjurnal';
    public $timestamps = false;
}

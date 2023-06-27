<?php

namespace App\Models\Simrs\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mruangranap extends Model
{
    use HasFactory;
    protected $table = 'rs24';
    protected $gurded = ['id'];
    public $timestamps = false;
    protected $keyType = 'string';

}

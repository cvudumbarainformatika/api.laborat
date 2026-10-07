<?php

namespace App\Models\Simrs\Rajal\Igd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visum extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'visum';
    protected $guarded = ['id'];
}

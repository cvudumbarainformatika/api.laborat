<?php

namespace App\Models\Simrs\Kasir;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karcis extends Model
{
    use HasFactory;
    protected $table = 'karcislog';
    protected $guarded = ['id'];
}

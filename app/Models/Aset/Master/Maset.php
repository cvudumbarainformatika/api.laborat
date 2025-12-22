<?php

namespace App\Models\Aset\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maset extends Model
{
    use HasFactory;
    protected $connection = 'aset';
    protected $table = 'maset';
    protected $guarded = ['id'];
}

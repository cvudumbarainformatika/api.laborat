<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mruangan extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $table      = 'ruangs';
    protected $guarded = ['id'];
}

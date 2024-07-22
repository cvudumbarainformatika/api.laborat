<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MkamarRanap extends Model
{
    use HasFactory;
    protected $table      = 'rs25';
    protected $guarded = ['id'];
}

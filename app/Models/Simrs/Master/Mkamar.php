<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mkamar extends Model
{
    use HasFactory;
    protected $table      = 'rs24';
    protected $guarded = ['id'];
}

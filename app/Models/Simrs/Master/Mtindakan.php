<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mtindakan extends Model
{
    use HasFactory;
    protected $table = 'rs30';
    protected $guarded = ['id'];
}

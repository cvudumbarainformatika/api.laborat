<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mkelamin extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'kelamin';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];
}

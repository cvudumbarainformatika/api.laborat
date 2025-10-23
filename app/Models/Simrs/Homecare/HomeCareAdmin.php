<?php

namespace App\Models\Simrs\Homecare;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeCareAdmin extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'mysql';
}

<?php

namespace App\Models\Simrs\Pendaftaran\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sepranap extends Model
{
    use HasFactory;
    protected $table = 'rs227';
    protected $guarded = ['id'];
}

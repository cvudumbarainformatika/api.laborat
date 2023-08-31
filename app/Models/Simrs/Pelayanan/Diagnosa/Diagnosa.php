<?php

namespace App\Models\Simrs\Pelayanan\Diagnosa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosa extends Model
{
    use HasFactory;
    protected $table = 'rs101';
    protected $guarded = ['id'];
}

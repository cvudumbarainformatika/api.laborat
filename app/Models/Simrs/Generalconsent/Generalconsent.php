<?php

namespace App\Models\Simrs\Generalconsent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Generalconsent extends Model
{
    use HasFactory;
    protected $table = 'gencons';
    protected $guarded = ['id'];
}

<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataarsip extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'data_arsip';
    protected $guarded = ['id'];
}

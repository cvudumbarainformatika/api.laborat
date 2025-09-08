<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mcabinet extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'master_fillingcabinet';
    protected $guarded = ['id'];
}

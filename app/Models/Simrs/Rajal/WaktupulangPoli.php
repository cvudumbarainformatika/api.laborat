<?php

namespace App\Models\Simrs\Rajal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaktupulangPoli extends Model
{
    use HasFactory;
    protected $table = 'rs141';
    protected $guarded = ['id'];
    public $timestamps = false;
}

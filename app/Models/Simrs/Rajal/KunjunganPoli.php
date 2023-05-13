<?php

namespace App\Models\Simrs\Rajal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganPoli extends Model
{
    use HasFactory;
    protected $table = 'rs17';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
}

<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rstigapuluhtarif extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'rs30tarif';
    protected $guarded = ['id'];
    // public $timestamps = false;
}

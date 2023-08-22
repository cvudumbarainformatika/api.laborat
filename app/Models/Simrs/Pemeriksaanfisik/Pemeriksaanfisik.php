<?php

namespace App\Models\Simrs\Pemeriksaanfisik;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemeriksaanfisik extends Model
{
    use HasFactory;
    protected $table = 'rs236';
    protected $guarded = ['id'];
}

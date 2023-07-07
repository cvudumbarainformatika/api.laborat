<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrianambil extends Model
{
    use HasFactory;
    protected $table = 'antrian_ambil';
    protected $guarded = ['id'];
}

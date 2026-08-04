<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdukasiFarmasi extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $table = 'pelayanan_edukasi_farmasis';
    protected $guarded = ['id'];
}

<?php

namespace App\Models\Simrs\UnitPengelolahArsip;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeminjamanHeder extends Model
{
    use HasFactory;
    protected $connection = 'arsip';
    protected $table = 'tpeminjaman_h';
    protected $guarded = ['id'];
}

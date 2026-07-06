<?php

namespace App\Models\Siasik\Anggaran;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anggaran_Pendapatan_pak extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'anggaran_pendapatan_pak';
    public $timestamps = false;
}

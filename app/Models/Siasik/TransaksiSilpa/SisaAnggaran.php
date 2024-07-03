<?php

namespace App\Models\Siasik\TransaksiSilpa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SisaAnggaran extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'silpa';
    public $timestamps = false;
}

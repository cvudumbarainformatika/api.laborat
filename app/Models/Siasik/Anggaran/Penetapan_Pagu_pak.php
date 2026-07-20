<?php

namespace App\Models\Siasik\Anggaran;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penetapan_Pagu_pak extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'penetapan_pagu_pak';
    public $timestamps = false;
}

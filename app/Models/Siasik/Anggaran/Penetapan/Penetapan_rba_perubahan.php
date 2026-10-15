<?php

namespace App\Models\Siasik\Anggaran\Penetapan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penetapan_rba_perubahan extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'penetapan_rba_perubahan';
}

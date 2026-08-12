<?php

namespace App\Models\Simrs\Rajal\Igd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TtdDokumenIgd extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'ttddokumenigd';
    protected $guarded = ['id'];
}

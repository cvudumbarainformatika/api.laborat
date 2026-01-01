<?php

namespace App\Models\Siasik\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Master_Satuan extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $table = 'satuan_barang';
    
    protected $guarded = ['id'];
}

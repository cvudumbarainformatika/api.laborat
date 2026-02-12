<?php

namespace App\Models\Simrs\Penunjang\Cssd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangCssd extends Model
{
    use HasFactory;
    protected $table = 'barang_cssd';
    protected $guarded = ['id'];
    public $timestamps = false;
}

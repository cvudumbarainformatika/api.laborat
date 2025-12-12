<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsSuratKontrol extends Model
{
    use HasFactory;
    protected $table = 'bpjs_surat_kontrol';
    protected $guarded = [];
    public $timestamps = false;
}

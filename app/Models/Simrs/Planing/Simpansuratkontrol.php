<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simpansuratkontrol extends Model
{
    use HasFactory;
    protected $table = 'bpjs_surat_kontrol';
    protected $guarded = ['id'];
}

<?php

namespace App\Models\Simrs\Penjaminan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListCasmixRanap extends Model
{
    use HasFactory;

    protected $table = 'listkirimcasmixranap';
    protected $guarded = ['id'];
}

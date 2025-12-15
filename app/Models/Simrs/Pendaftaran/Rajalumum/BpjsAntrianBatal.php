<?php

namespace App\Models\Simrs\Pendaftaran\Rajalumum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsAntrianBatal extends Model
{
    use HasFactory;
    protected $table = 'bpjs_antrian_batal';
    protected $guarded = ['id'];
}

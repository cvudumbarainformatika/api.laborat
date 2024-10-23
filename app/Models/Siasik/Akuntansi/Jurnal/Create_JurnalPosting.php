<?php

namespace App\Models\Siasik\Akuntansi\Jurnal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Create_JurnalPosting extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'jurnal_postingotom';
}

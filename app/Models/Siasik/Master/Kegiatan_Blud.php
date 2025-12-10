<?php

namespace App\Models\Siasik\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan_Blud extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    // protected $guarded = ['no'];
    protected $primaryKey = 'no';
    protected $table = 'kegiatan_blud';
    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';
    protected $guarded = [];
}

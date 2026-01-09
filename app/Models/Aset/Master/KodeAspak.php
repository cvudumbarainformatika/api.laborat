<?php

namespace App\Models\Aset\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodeAspak extends Model
{
    use HasFactory;
    protected $connection = 'aset';
    public $timestamps = false;
    protected $table = 'kodeaspak';
    protected $guarded = ['id'];
}

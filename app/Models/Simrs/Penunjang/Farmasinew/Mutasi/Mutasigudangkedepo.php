<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Mutasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutasigudangkedepo extends Model
{
    use HasFactory;
    protected $table = 'mutasi_gudangdepo';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

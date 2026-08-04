<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meso extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $table = 'pelayanan_mesos';
    protected $guarded = ['id'];
}

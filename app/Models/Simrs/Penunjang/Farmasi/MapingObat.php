<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapingObat extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'mapingobat';
}

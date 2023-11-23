<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Simrs\Master\Diagnosa_m;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervensikeperawatan extends Model
{
    use HasFactory;
    protected $table = 'intervensikeperawatan';
    protected $guarded = ['id'];
}

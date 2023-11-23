<?php

namespace App\Models\Simrs\Pelayanan\Diagnosa;

use App\Models\Simrs\Master\Diagnosa_m;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosakeperawatan extends Model
{
    use HasFactory;
    protected $table = 'diagnosakeperawatan';
    protected $guarded = ['id'];
}

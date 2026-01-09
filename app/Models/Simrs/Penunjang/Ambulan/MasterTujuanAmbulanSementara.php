<?php

namespace App\Models\Simrs\Penunjang\Ambulan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterTujuanAmbulanSementara extends Model
{
    use HasFactory;
    protected $table = 'rs281_sementara';
    protected $guarded = ['id'];
}

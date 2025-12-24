<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpemeriksaaanLabSementara extends Model
{
    use HasFactory;

    protected $table = 'rs49_sementara';
    protected $guarded = ['id'];
}

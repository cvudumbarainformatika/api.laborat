<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPemeriksaanRadiologiSementara extends Model
{
    use HasFactory;

    protected $table = 'rs47_sementara';
    protected $guarded = ['id1'];
    protected $primaryKey = 'id1';
}

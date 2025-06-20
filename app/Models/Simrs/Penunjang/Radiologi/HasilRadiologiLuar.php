<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class HasilRadiologiLuar extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'rs272';
    protected $guarded = ['id'];
    public $timestamps = false;
}

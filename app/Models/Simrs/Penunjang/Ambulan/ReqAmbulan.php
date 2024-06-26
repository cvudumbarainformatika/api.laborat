<?php

namespace App\Models\Simrs\Penunjang\Ambulan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReqAmbulan extends Model
{
    use HasFactory;
    protected $table = 'rs276';
    protected $guarded = ['id'];
}

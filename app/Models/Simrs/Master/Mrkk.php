<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mrkk extends Model
{
    use HasFactory;
    protected $table      = 'm_rkk';
    protected $guarded = ['id'];

    protected $casts = [
        'jenjang' => 'array', // otomatis di-cast jadi array
        'jenis' => 'array', // otomatis di-cast jadi array
    ];
   
}

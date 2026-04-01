<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssasemenPraBedah extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'komplikasi' => 'array'
    ];
}

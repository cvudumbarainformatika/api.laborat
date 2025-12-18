<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgicalSafety extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'signIn' => 'array',
        'signOut' => 'array',
        'timeOut' => 'array',
    ];
}

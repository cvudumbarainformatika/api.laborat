<?php

namespace App\Models\Simrs\Homecare;

use Illuminate\Database\Eloquent\Model;

class InformConcern extends Model
{
    protected $table = 'homecare_inform_concerns';

    protected $connection = 'mysql';

    protected $guarded = ['id'];

    protected $attributes = [
        'homecare_24_hours' => false,
    ];

    protected $casts = [
        'homecare_24_hours' => 'boolean',
        'signed_at' => 'date:Y-m-d',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(HomeCareKunjungan::class, 'noreg', 'noreg');
    }
}

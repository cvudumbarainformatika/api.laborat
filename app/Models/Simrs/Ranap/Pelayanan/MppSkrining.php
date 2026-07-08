<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Simpeg\Petugas;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MppSkrining extends Model
{
    use HasFactory, LogsActivity;

    protected $connection = 'mysql';
    protected $table = 'mpp_skrinings';
    protected $guarded = ['id'];

    protected $casts = [
        'skrining' => 'array',
    ];

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kdpegsimrs');
    }
}

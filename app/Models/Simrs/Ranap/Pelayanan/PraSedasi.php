<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Simpeg\Petugas;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PraSedasi extends Model
{
    use HasFactory, LogsActivity;

    protected $connection = 'mysql';
    protected $table = 'pra_sedasi';
    protected $guarded = ['id'];

    protected $casts = [
        'kajian_sistem' => 'array',
        'laboratorium' => 'array',
        'diagnosis' => 'array',
        'penyulit_sedasi_lain' => 'array',
        'teknik_khusus' => 'array',
    ];

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kdpegsimrs');
    }
}

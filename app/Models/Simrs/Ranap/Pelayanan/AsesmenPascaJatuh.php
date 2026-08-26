<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsesmenPascaJatuh extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'asesmen_pasca_jatuhs';
    protected $guarded = ['id'];

    protected $casts = [
        'details' => 'array'
    ];

    public function pegawai()
    {
        return $this->belongsTo(Petugas::class, 'kdpegsimrs', 'kdpegsimrs');
    }
}

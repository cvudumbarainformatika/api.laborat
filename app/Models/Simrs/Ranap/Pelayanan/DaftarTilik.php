<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Simpeg\Petugas;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaftarTilik extends Model
{
    use HasFactory, LogsActivity;

    protected $connection = 'mysql';
    protected $table = 'daftartilik';
    protected $guarded = ['id'];

    protected $casts = [
        'pre_checklist' => 'array',
        'paska_checklist' => 'array'
    ];

    public function petugas_pre_pengantar()
    {
        return $this->belongsTo(Petugas::class, 'pre_petugas_pengantar', 'kdpegsimrs');
    }

    public function petugas_pre_penerima()
    {
        return $this->belongsTo(Petugas::class, 'pre_petugas_penerima', 'kdpegsimrs');
    }

    public function petugas_paska_pengantar()
    {
        return $this->belongsTo(Petugas::class, 'paska_petugas_pengantar', 'kdpegsimrs');
    }

    public function petugas_paska_penerima()
    {
        return $this->belongsTo(Petugas::class, 'paska_petugas_penerima', 'kdpegsimrs');
    }
}

<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Sigarang\Pegawai;
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

    public function dr_operator()
    {
        return $this->belongsTo(Pegawai::class, 'dokter_operator', 'kdpegsimrs');
    }
    public function dr_anastesi()
    {
        return $this->belongsTo(Pegawai::class, 'dokter_anastesi', 'kdpegsimrs');
    }
    public function pen_anastesi()
    {
        return $this->belongsTo(Pegawai::class, 'penata_anastesi', 'kdpegsimrs');
    }
    public function per_instrumen()
    {
        return $this->belongsTo(Pegawai::class, 'perawat_instrumen', 'kdpegsimrs');
    }
    public function per_sirkuler()
    {
        return $this->belongsTo(Pegawai::class, 'perawat_sirkuler', 'kdpegsimrs');
    }
    public function ass1()
    {
        return $this->belongsTo(Pegawai::class, 'asisten_1', 'kdpegsimrs');
    }
    public function ass2()
    {
        return $this->belongsTo(Pegawai::class, 'asisten_2', 'kdpegsimrs');
    }
}

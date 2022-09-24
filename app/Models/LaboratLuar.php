<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratLuar extends Model
{
    use HasFactory;
    protected $table = 'lab_luar';

    protected $fillable =
    [
        'nama',
        'kelamin',
        'alamat',
        'pengirim',
        'tgl_lahir',
        'tgl',
        'nota',
        'kd_lab',
        'jml',
        'tarif_sarana',
        'tarif_pelayanan',
        'jenispembayaran',
        'jam_sampel_selesai',
        'jam_sampel_diambil',
        'sampel_selesai',
        'sampel_diambil',
        'perusahaan_id',
        'noktp',
        'nosurat',
        'temp_lahir',
        'agama',
        'nohp',
        'kode_pekerjaan',
        'nama_pekerjaan'
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }
    public function pemeriksaan_laborat() // data master
    {
        return $this->belongsTo(PemeriksaanLaborat::class,'kd_lab','rs1');
    }
}

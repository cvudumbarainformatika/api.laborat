<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumenUpload extends Model
{
    use HasFactory;
    protected $table = 'upload_dok_luar';
    protected $guarded = ['id'];

    // protected $casts = [
    //   'resikoJatuh' => 'array',
    // ];

    public function pegawai()
    {
       return $this->belongsTo(Pegawai::class,'user_input', 'id');
    }
}

<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembatalanPelayanan extends Model
{
    use HasFactory;

    protected $table = 'pembatalan_pelayanans';

    protected $guarded = ['id'];

    public function dpjp()
    {
        return $this->belongsTo(Petugas::class, 'dpjp_kodesimrs', 'kdpegsimrs');
    }
}

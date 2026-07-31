<?php

namespace App\Models\Simrs\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Simpeg\Petugas;
use Illuminate\Support\Facades\DB;

class RekonsiliasiObatPersetujuan extends Model
{
    use HasFactory;

    protected $table = 'rekonsiliasi_obat_persetujuan';
    protected $guarded = ['id'];
    protected $appends = ['nama_ruangan'];

    public function getNamaRuanganAttribute()
    {
        $kdruang = $this->kdruang;
        if (!$kdruang) {
            return '-';
        }

        if ($kdruang === 'POL014' || substr($kdruang, 0, 3) === 'POL') {
            $poli = DB::table('rs19')->where('rs1', $kdruang)->first();
            if ($poli) {
                return $poli->rs2;
            }
        }

        $ruang = DB::table('rs24')->where('rs1', $kdruang)->first();
        if ($ruang) {
            return $ruang->rs2;
        }

        return $kdruang;
    }

    public function user_petugas()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'petugas');
    }
}

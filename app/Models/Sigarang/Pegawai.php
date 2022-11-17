<?php

namespace App\Models\Sigarang;

use App\Models\Pegawai\Jabatan;
use App\Models\Pegawai\JabatanTambahan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $table = 'pegawai';
    protected $fillable = [];

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan', 'kode_jabatan');
    }
    public function jabatanTambahan()
    {
        return $this->belongsTo(JabatanTambahan::class, 'jabatan_tmb', 'kode_jabatan');
    }


    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nip', 'LIKE', '%' . $query . '%')
                ->orWhere('nama', 'LIKE', '%' . $query . '%');
            // ->orWhere('kodemapingrs', 'LIKE', '%' . $query . '%');
        });
    }
}

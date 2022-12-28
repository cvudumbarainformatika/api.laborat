<?php

namespace App\Models\Sigarang;

use App\Models\Pegawai\Jabatan;
use App\Models\Pegawai\JabatanTambahan;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Ruangan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $table = 'pegawai';
    protected $guarded = ['id'];

    public $timestamps = false;

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan', 'kode_jabatan');
    }
    public function jabatanTambahan()
    {
        return $this->belongsTo(JabatanTambahan::class, 'jabatan_tmb', 'kode_jabatan');
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function jadwal()
    {
        return $this->hasMany(JadwalAbsen::class);
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'ruang', 'koderuangan');
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

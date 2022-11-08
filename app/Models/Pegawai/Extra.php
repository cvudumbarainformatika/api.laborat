<?php

namespace App\Models\Pegawai;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Extra extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function prota()
    {
        return $this->belongsTo(Prota::class);
    }
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('tanggal', 'LIKE', '%' . $query . '%');
            // ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}

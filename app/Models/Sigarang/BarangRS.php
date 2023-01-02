<?php

namespace App\Models\Sigarang;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangRS extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
    }
    public function barang108()
    {
        return $this->belongsTo(Barang108::class, 'kode_108', 'kode');
    }

    public function mapingbarang()
    {
        return $this->hasOne(MapingBarang::class, 'kode_rs', 'kode');
    }

    public function mapingdepo()
    {
        return $this->hasOne(MapingBarangDepo::class, 'kode_rs', 'kode');
    }


    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%')
                ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}

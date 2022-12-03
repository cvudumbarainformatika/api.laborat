<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapingBarangDepo extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';

    protected $guarded = ['id'];

    public function barangrs()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
    }
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'kode_gudang', 'kode');
    }
}

<?php

namespace App\Models\Siasik\Anggaran;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyesuaian_Prioritas_Rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'penyesesuaianperioritas_rinci';
    protected $appends = ['jenis'];

    public function getJenisAttribute()
    {
        $koders = $this->koders;

        if (!$koders) {
            return null;
        }

        if (str_starts_with($koders, 'JS')) {
            return 'Jasa';
        }

        if (str_starts_with($koders, 'RS-')) {
            return 'Barang';
        }

        if (str_starts_with($koders, '1.3')) {
            return 'Modal';
        }

        if (str_starts_with($koders, '1.1')) {
            return 'Farmasi';
        }

        return 'Lainnya';
    }
}

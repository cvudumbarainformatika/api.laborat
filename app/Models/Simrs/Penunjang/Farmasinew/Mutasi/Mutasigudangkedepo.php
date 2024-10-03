<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Mutasi;

use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutasigudangkedepo extends Model
{
    use HasFactory;
    protected $table = 'mutasi_gudangdepo';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function header()
    {
        return $this->belongsTo(Permintaandepoheder::class, 'no_permintaan', 'no_permintaan');
    }
    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
}

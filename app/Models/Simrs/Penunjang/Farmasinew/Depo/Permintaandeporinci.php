<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaandeporinci extends Model
{
    use HasFactory;
    protected $table = 'permintaan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function permintaanobatheder()
    {
        return $this->hasOne(Permintaandepoheder::class, 'no_permintaan', 'no_permintaan');
    }
}

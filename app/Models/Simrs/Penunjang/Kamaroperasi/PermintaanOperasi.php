<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanOperasi extends Model
{
    use HasFactory;
    protected $table = 'rs200';
    protected $guarded = ['id'];

    public function kunjunganranap()
    {
        return $this->hasOne(Kunjunganranap::class, 'rs1', 'rs1');
    }

    public function kunjunganrajal()
    {
        return $this->hasOne(KunjunganPoli::class, 'rs1', 'rs1');
    }
}

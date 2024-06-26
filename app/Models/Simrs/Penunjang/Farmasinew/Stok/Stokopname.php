<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Stok;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stokopname extends Model
{
    use HasFactory;
    protected $table = 'stokopname';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function masterobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
    public function tutup()
    {
        return $this->hasOne(TutupOpname::class, 'tglopname', 'tglopname');
    }
}

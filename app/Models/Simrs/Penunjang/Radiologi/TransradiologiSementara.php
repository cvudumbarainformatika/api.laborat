<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class TransradiologiSementara extends Model
{
    use HasFactory;
    protected $table = 'rs48_sem';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $appends = ['subtotal'];
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';


    public function relmasterpemeriksaan()
    {
        return $this->belongsTo(Mpemeriksaanradiologi::class, 'rs4','rs1');
    }

    public function getSubtotalAttribute()
    {
        $harga1 = (int) $this->rs6;
        $harga2 = (int) $this->rs8;
        $jumlah = (int) $this->rs24;
        $biaya = $harga1+$harga2;
        return ($biaya*$jumlah);
    }
}

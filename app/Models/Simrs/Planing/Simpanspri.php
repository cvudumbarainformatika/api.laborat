<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simpanspri extends Model
{
    use HasFactory;
    protected $table = 'bpjs_spri';
    protected $guarded = ['id'];

    public function jadwaloperasi()
    {
        return $this->hasOne(jadwaloperasi::class, 'noreg', 'noreg');
    }
}

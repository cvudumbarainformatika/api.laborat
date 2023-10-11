<?php

namespace App\Models\Simrs\Planing;

use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transrujukan extends Model
{
    use HasFactory;
    protected $table = 'rs288';
    protected $guarded = ['id'];

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }

    public function relmpoli()
    {
        return $this->belongsTo(Mpoli::class, 'rs1', 'poli');
    }

    public function relmpolix()
    {
        return $this->belongsTo(Mpoli::class, 'rs1', 'polirujukan');
    }
}

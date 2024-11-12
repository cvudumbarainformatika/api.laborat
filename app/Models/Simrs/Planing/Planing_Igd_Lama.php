<?php

namespace App\Models\Simrs\Planing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planing_Igd_Lama extends Model
{
    use HasFactory;
    protected $table = 'rs141';
    protected $guarded = ['id'];

    public function planranap()
    {
        return $this->hasOne(Planing_Igd_ranap::class, 'id', 'id_heder');
    }

    public function planrujukan()
    {
        return $this->hasOne(Planing_Igd_Rujukan::class, 'id', 'id_heder');
    }

    public function planpulang()
    {
        return $this->hasOne(Planing_Igd_Pulang::class, 'id', 'id_heder');
    }
}

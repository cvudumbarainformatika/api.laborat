<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifVisiteDanKamarSementara extends Model
{
    use HasFactory;

    protected $table = 'rs30tarif_sementara';
    protected $guarded = ['id'];
}

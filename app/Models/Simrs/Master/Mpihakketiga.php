<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpihakketiga extends Model
{
    use HasFactory;
    protected $table = 'pihak_ketiga';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';
}

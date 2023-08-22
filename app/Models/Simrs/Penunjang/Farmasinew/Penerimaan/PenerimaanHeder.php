<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenerimaanHeder extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'penerimaan_h';
    protected $guarded = ['id'];
}

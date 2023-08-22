<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanRinci extends Model
{
    use HasFactory;
    protected $table = 'penerimaan_r';
    protected $guarded = ['id'];
}

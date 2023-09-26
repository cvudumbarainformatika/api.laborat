<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanOperasi extends Model
{
    use HasFactory;
    protected $table = 'rs200';
    protected $guarded = ['id'];
}

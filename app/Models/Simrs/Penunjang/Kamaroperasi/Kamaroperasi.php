<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamaroperasi extends Model
{
    use HasFactory;
    protected $table = 'rs54';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $keyType = 'string';
}

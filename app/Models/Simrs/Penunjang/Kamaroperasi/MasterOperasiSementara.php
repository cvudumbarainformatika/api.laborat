<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterOperasiSementara extends Model
{
    use HasFactory;
    protected $table = 'rs53_sementara';
    protected $guarded = ['idx'];
    public $primaryKey = 'idx';
}

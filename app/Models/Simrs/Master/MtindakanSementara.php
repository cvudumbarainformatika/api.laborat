<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtindakanSementara extends Model
{
    use HasFactory;

    use HasFactory;
    protected $table = 'rs30_sementara';
    protected $guarded = ['idx'];
    public $primaryKey = 'idx';
}

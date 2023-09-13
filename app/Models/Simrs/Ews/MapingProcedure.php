<?php

namespace App\Models\Simrs\Ews;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapingProcedure extends Model
{
    use HasFactory;
    protected $table = 'prosedur_mapping';
    protected $guarded = ['id'];
    public $timestamps = false;
}

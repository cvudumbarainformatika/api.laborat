<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class HasilRadiologi extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'rs151';
    protected $guarded = ['id'];

    public $timestamps = false; // <--- Ini menonaktifkan created_at & updated_at
}

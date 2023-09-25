<?php

namespace App\Models\Simrs\Penunjang\Lain;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lain extends Model
{
    use HasFactory;
    protected $table = 'rs107';
    protected $guarded = ['id'];
    public $timestamps = false;

    // public function details()
    // {
    //     return $this->hasMany(Laboratpemeriksaan::class, 'rs2', 'nota');
    // }
}

<?php

namespace App\Models\Simrs\Penunjang\Laborat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratMeta extends Model
{
    use HasFactory;
    protected $table = 'rs51_meta';
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs2', 'nota');
    }
}

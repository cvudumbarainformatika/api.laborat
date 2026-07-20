<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnamnesisSkrinig extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = '209_skrining';
    protected $guarded = ['id'];


    public function user_input()
    {
        return  $this->belongsTo(Petugas::class, 'kdpegsimrs', 'kdpegsimrs');
    }
}

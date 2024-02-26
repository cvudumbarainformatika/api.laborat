<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Pegawai\Mpegawaisimpeg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anamnesis extends Model
{
    use HasFactory;
    protected $table = 'rs209';
    protected $guarded = ['id'];


    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'user');
    }
}

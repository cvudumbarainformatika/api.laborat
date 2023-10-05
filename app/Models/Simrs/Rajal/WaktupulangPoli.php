<?php

namespace App\Models\Simrs\Rajal;

use App\Models\Poli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaktupulangPoli extends Model
{
    use HasFactory;
    protected $table = 'rs141';
    protected $guarded = ['id'];

    public function masterpoli()
    {
        return $this->hasOne(Poli::class, 'rs1', 'rs3');
    }
}

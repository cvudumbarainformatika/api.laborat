<?php

namespace App\Models\Simrs\Kasir;

use App\Models\Simrs\Master\Rstigapuluhtarif;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rstigalimax extends Model
{
    use HasFactory;
    protected $table = 'rs35x';
    protected $guarded = ['id'];

    public function rstigapuluhtarif()
    {
        return $this->hasMany(Rstigapuluhtarif::class, 'rs1','rs1');
    }

}

<?php

namespace App\Models\Simrs\Rajal;

use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listkonsulantarpoli extends Model
{
    use HasFactory;
    protected $table = 'listkonsulanpoli';
    protected $guarded = ['id'];

    public function mpasien()
    {
        return $this->hasMany(Mpasien::class, 'rs1', 'norm');
    }

    public function seprajal()
    {
        return $this->hasOne(Seprajal::class, 'rs1', 'noreg_lama');
    }
}

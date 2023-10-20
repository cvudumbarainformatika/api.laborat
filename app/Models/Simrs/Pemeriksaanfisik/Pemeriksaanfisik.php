<?php

namespace App\Models\Simrs\Pemeriksaanfisik;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemeriksaanfisik extends Model
{
    use HasFactory;
    protected $table = 'rs236';
    protected $guarded = ['id'];

    public function anatomys()
    {
        return $this->hasMany(Pemeriksaanfisikdetail::class, 'rs236_id', 'id');
    }
    public function detailgambars()
    {
        return $this->hasMany(Pemeriksaanfisiksubdetail::class, 'rs236_id', 'id');
    }
    public function pemeriksaankhususmata()
    {
        return $this->hasOne(Polimata::class, 'rs1', 'rs1');
    }
}

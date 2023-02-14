<?php

namespace App\Models\Executive;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderPenerimaan extends Model
{
    use HasFactory;
    // protected $connection = 'kepex';
    protected $table = 'rs258';
    protected $guarded = ['id'];

    public function detail_penerimaan()
    {
        return $this->hasMany(DetailPenerimaan::class, 'rs1', 'rs1');
    }
}

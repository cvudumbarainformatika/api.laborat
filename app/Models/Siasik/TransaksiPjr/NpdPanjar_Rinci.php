<?php

namespace App\Models\Siasik\TransaksiPjr;

use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpdPanjar_Rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'npdpanjar_rinci';
    public $timestamps = false;
    // public function nota_head()
    // {
    //     return $this->belongsTo(NotaPanjar_Header::class, 'nonpdpanjar', 'nonpd');
    // }

    public function tampung()
    {
        return $this->belongsTo(PergeseranPaguRinci::class, 'idpp', 'idpp');
    }
}

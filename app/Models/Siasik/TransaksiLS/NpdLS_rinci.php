<?php

namespace App\Models\Siasik\TransaksiLS;

use App\Models\Siasik\Master\Akun_Kepmendg50;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpdLS_rinci extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'npdls_rinci';
    // public function kodeall()
    // {
    //     return $this->belongsTo(Akun_permendagri50::class, 'kodeall', 'koderek50');
    // }
    public function akun(){
        return $this->hasOne(Akun_Kepmendg50::class,'koderek50', 'kodeall');
    }
    public function cp(){
        return $this->hasMany(Contrapost::class,'nonpd','nonpdls');
    }
    public function headerls()
    {
        return $this->belongsTo(NpdLS_heder::class, 'nonpdls', 'nonpdls');
    }
}

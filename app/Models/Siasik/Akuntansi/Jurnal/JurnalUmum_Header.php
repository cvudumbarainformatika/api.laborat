<?php

namespace App\Models\Siasik\Akuntansi\Jurnal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalUmum_Header extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'jurnalumum_heder';
    public $timestamps = false;

    public function rincianjurnalumum()
    {
        return $this->hasMany(JurnalUmum_Rinci::class, 'nobukti', 'nobukti');
    }

}

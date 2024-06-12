<?php

namespace App\Models\Siasik\TransaksiLS;

use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrapost extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'contrapost';
    protected $timestamp = false;

    public function mapbidang()
    {
        return $this->belongsTo(Mapping_Bidang_Ptk_Kegiatan::class, 'kodekegiatanblud', 'kodekegiatan');
    }
}
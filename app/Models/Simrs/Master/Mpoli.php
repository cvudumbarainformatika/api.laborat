<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpoli extends Model
{
    use HasFactory;
    protected $table = 'rs19';
    protected $guarded = [];
    public $timestamps = false;
    public $primarykey = 'rs1';
    protected $keyType = 'string';

    // public function scopeListpoli($data)
    // {
    //     return $data->select([
    //         'rs1 as kodepoli',
    //         'rs2 as polirs',
    //         'rs3 as jenispoli',
    //         'rs4 as jenisruangan',
    //         'rs5 as statukeaktifan',
    //         'rs6 as kodemapingbpjs',
    //         'rs7 as polimapingbpjs',
    //     ]);
    // }

    public function jumlahkunjunganpolix()
    {
        return $this->hasMany(KunjunganPoli::class,'rs8','rs1');
    }

    public function jumlahkunjunganpoli()
    {
        return $this->hasMany(KunjunganPoli::class,'rs8','rs1');
    }

}

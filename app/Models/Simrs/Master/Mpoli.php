<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpoli extends Model
{
    use HasFactory;
    protected $table = 'rs19';
    protected $guarded = [];
    public $primarykey = 'rs1';
    protected $keyType = 'string';

    public function scopeListpoli($data)
    {
        return $data->select([
            'rs1 as kodepoli',
            'rs2 as polirs',
            'rs3 as jenispoli',
            'rs4 as jenisruangan',
            'rs5 as statukeaktifan',
            'rs6 as kodemapingbpjs',
            'rs7 as polimapingbpjs',
        ]);
    }

}

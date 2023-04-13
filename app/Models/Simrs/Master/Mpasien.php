<?php

namespace App\Models\Simrs\Master;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpasien extends Model
{
    use HasFactory;
    protected $table = 'rs15';

    protected $appends = ['usia'];

    public function getUsiaAttribute()
    {
        $dateOfBirth = $this->tgllahir;
        $years = Carbon::parse($dateOfBirth)->age;
        return $years." Tahun";
    }

    // public function agama()
    // {
    //     return $this->belongsTo(Magama::class, 'rs22', 'rs1');
    // }

    public function getRs31Attribute($value)
    {
        return $value > '1'? 'Lama':'Baru' ;
    }

    public function scopePasien($data)
    {
        return $data->select([
                'rs1 as norm',
                'rs2 AS nama',
                'rs3 AS sapan',
                'rs4 AS alamat',
                'rs5 AS kelurahan',
                'rs6 AS kecamatan',
                'rs7 AS rt',
                'rs8 AS rw',
                'rs9 AS kodepos',
                'rs10 AS propinsi',
                'rs11 AS kabupaten',
                'rs12 AS pekerjaan',
                'rs13 AS keterangankerja',
                'rs14 AS telprumah',
                'rs15 AS hp',
                'rs16 AS tgllahir',
                'rs17 AS kelamin',
                'rs18 AS statuskawin',
                'rs31 as type',
                'rs19 AS pendidikan',
                'rs20 AS goldarah',
                'rs21 AS rh',
                'rs22 AS agama',
                'rs36 AS normlama',
                'rs37 AS tmplahir',
                'rs38 AS nomap',
                'rs39 AS suku',
                'rs42 as statuscetak',
                'rs43 AS namafoto',
                'rs46 as noka',
                'rs47 as icd',
                'rs48 as tglrujuk',
                'rs49 as noktp',
                'rs50 as statusrstinggi',
                'rs55 as telepon',
                'bahasa as bahasa'
            ])->where('rs1','<>','')
            ->limit(50);
    }

    public function scopeFilter($cari,array $reqs)
    {
        //$data = Mpasien::Pasien();
       // $query = $data->get();
        $cari->when($reqs['q'] ?? false,
                function($data, $query){
                    return $data->where('rs1', 'LIKE', '%' . $query . '%')
                        ->orWhere('rs2', 'LIKE', '%' . $query . '%')
                        ->orWhere('rs46', 'LIKE', '%' . $query . '%')
                        ->orWhere('rs49', 'LIKE', '%' . $query . '%')
                        ->orWhere('rs46', 'LIKE', '%' . $query . '%')
                        ->orWhere('rs55', 'LIKE', '%' . $query . '%')
                        ->orderBy('rs1');
                });
    }

    // public function scopeFilter($cari,array $reqs)
    //     {
    //         $cari->select([
    //             'rs1 as norm',
    //             'rs2 AS nama',
    //             'rs3 AS sapan',
    //             'rs4 AS alamat',
    //             'rs5 AS kelurahan',
    //             'rs6 AS kecamatan',
    //             'rs7 AS rt',
    //             'rs8 AS rw',
    //             'rs9 AS kodepos',
    //             'rs10 AS propinsi',
    //             'rs11 AS kabupaten',
    //             'rs12 AS pekerjaan',
    //             'rs13 AS keterangankerja',
    //             'rs14 AS telprumah',
    //             'rs15 AS hp',
    //             'rs16 AS tgllahir',
    //             'rs17 AS kelamin',
    //             'rs18 AS statuskawin',
    //             'rs31 as type',
    //             'rs19 AS pendidikan',
    //             'rs20 AS goldarah',
    //             'rs21 AS rh',
    //             'rs22 AS agama',
    //             'rs36 AS normlama',
    //             'rs37 AS tmplahir',
    //             'rs38 AS nomap',
    //             'rs39 AS suku',
    //             'rs42 as statuscetak',
    //             'rs43 AS namafoto',
    //             'rs46 as noka',
    //             'rs47 as icd',
    //             'rs48 as tglrujuk',
    //             'rs49 as noktp',
    //             'rs50 as statusrstinggi',
    //             'rs55 as telepon',
    //             'bahasa as bahasa'
    //         ])->when($reqs['q'] ?? false,
    //         function($cari, $query){
    //             return $cari->where('rs1', 'LIKE', '%' . $query . '%')
    //                 ->orWhere('rs2', 'LIKE', '%' . $query . '%')
    //                 ->orWhere('rs46', 'LIKE', '%' . $query . '%')
    //                 ->orWhere('rs49', 'LIKE', '%' . $query . '%')
    //                 ->orWhere('rs46', 'LIKE', '%' . $query . '%')
    //                 ->orWhere('rs55', 'LIKE', '%' . $query . '%')
    //                 ->orderBy('rs1')
    //                 ->limit(50);
    //         });

    //     }
}


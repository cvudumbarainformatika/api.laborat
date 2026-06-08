<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Arsip;

use App\Http\Controllers\Controller;
use App\Models\Simrs\UnitPengelolahArsip\MapHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class daftarArsipController extends Controller
{
    public function getdata()
    {
        $tahunDari = request('tahunDari');
        $tahunSampai = request('tahunSampai');
        $statusArsip = request('inactive'); // AKTIF / INAKTIF / kosong

        $data = MapHeder::with(['unitpengolah', 'kabinet',
            'rinciandalammap.dataarsip'])
            ->join('master_kode', 'kelompokMap_H.kodeklasifikasi', '=', 'master_kode.kode')
            ->select([
                'kelompokMap_H.*',
                'master_kode.nama as keterangan_kode',
                'master_kode.kode as kode_master',
                'master_kode.retensi as retensi',
            ])
            ->selectRaw('
                YEAR(CURDATE()) - kelompokMap_H.tahunMap as umur_berkas,
                CASE
                    WHEN (YEAR(CURDATE()) - kelompokMap_H.tahunMap) < master_kode.retensi
                    THEN "AKTIF"
                    ELSE "INAKTIF"
                END as status_arsip
            ')
            ->whereBetween('kelompokMap_H.tahunMap', [$tahunDari, $tahunSampai])
            ->when($statusArsip, function ($q) use ($statusArsip) {
                $q->having('status_arsip', '=', $statusArsip);
            })
            ->get();

        return new JsonResponse($data);
    }
}

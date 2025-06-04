<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Ruang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemakaianRuanganFsController extends Controller
{
    //

    public function getRuangan()
    {
        $data = Ruang::select(
            'kepegx.ruangs.kode',
            'kepegx.ruangs.uraian'
        )
            ->leftJoin('farmasi.permintaan_h', 'kepegx.ruangs.kode', '=', 'farmasi.permintaan_h.dari')
            ->whereNotNull('farmasi.permintaan_h.dari')
            ->groupBy('kepegx.ruangs.kode')
            ->get();

        return new JsonResponse([
            'data' => $data
        ]);
    }
    public function getData() {}
}

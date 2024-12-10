<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Penerimaan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenerimaanObatController extends Controller
{
    public function caripenerimaanobat()
    {
        $dari = request('tgldari') ;
        $sampai = request('tglsampai') ;

        $cari = PenerimaanHeder::with(
            [
                'gudang',
                'pihakketiga'
            ]
        )
        ->whereBetween('tglpenerimaan', [$dari, $sampai])
        ->get();

        return new JsonResponse($cari);
    }
}

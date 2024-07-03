<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllBillRekapByRuanganController extends Controller
{
    public function allBillRekapByRuangan()
    {
        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';

        $data = Rstigalimax::select('rs23.rs1','rs23.rs2','rs35x.rs16','rs24.rs5')
        ->leftjoin('rs23','rs23.rs1','rs35x.rs1')
        ->leftjoin('rs24','rs35x.rs16','rs24.rs4')
        ->where('rs35x.rs3','K1#')->whereBetween('rs23.rs4', [$dari, $sampai])
        ->groupBy('rs35x.rs1','rs35x.rs16')
        ->get();
        return new JsonResponse($data);
    }
}

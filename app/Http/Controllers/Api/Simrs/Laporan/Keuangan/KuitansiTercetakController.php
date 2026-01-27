<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use App\Models\Simrs\Kasir\Kwitansilog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KuitansiTercetakController extends Controller
{
    public function kuitansiTercetak()
    {
        $tgldari = request('tgldari').' 00:00:00';
        $tglsampai = request('tglsampai').' 23:59:59';
        $layanan = request('layanan');

        if($layanan === '1'){
            $data = Kwitansilog::whereNull('batal')->where('flag', 'Kasir Rajal')->whereBetween('tgl', [$tgldari, $tglsampai])->get();
            return new JsonResponse($data);
        }
    }
}

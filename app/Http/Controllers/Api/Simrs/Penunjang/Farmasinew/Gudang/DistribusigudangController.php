<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistribusigudangController extends Controller
{
    public function listpermintaandepo()
    {
        $gudang = request('kdgudang');
        $nopermintaan = request('no_permintaan');
        if ($gudang === '' || $gudang === null) {
            $listpermintaandepo = Permintaandepoheder::with('permintaanrinci')
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('flag', '1')
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        } else {

            $listpermintaandepo = Permintaandepoheder::with('permintaanrinci')
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('tujuan', $gudang)
                ->where('flag', '1')
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        }
    }
}

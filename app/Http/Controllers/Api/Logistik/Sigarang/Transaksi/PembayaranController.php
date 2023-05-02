<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    // cari kontrak nya dulu, yang sudah BAST tapi belum dibayar
    public function cariKontrak()
    {
        $data = Penerimaan::selectRaw('kontrak')
            // ->whereNot('tanggal_bast', null)
            // ->where('nilai_tagihan', '>=', 0)
            // ->where(function ($x) {
            //     $x->where('tanggal_bast', '<>', null)
            //         ->orWhere('nilai_tagihan', '>=', 0);
            // })
            ->where('no_bast', '<>', '')
            ->where('no_kwitansi', '')
            ->distinct()->get();

        return new JsonResponse($data);
    }
    public function ambilKontrak()
    {
        $data = KontrakPengerjaan::where('nokontrakx', request('kontrak'))
            ->with('penyedia')
            ->first();

        return new JsonResponse($data);
    }
}

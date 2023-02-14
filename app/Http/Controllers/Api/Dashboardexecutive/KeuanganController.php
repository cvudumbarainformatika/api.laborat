<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use App\Models\Agama;
use App\Models\Executive\DetailPenerimaan;
use App\Models\Executive\HeaderPenerimaan;
use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeuanganController extends Controller
{
    public function pendapatan()
    {
        $transaksiPendapatan = KeuTransPendapatan::where('noTrans', 'not like', "%TBP-UJ%")
            ->whereMonth('tgl', request('month'))
            ->whereYear('tgl', request('year'))->sum('nilai');

        $penerimaan = DetailPenerimaan::hasByNonDependentSubquery('header_penerimaan', function ($q) {
            $q->whereYear('rs2', request('year'))
                ->where('setor', '=', 'Setor')
                ->where(function ($query) {
                    $query->whereNull('tglBatal')
                        ->orWhere('tglBatal', '=', '0000-00-00 00:00:00');
                });
        })->with('header_penerimaan')
            ->sum('rs4');

        $penerimaan2 = DetailPenerimaan::hasByNonDependentSubquery('header_penerimaan', function ($q) {
            $q->whereYear('rs2', request('year'))
                ->where('setor', '<>', 'Setor')
                ->where(function ($query) {
                    $query->whereNull('tglBatal')
                        ->orWhere('tglBatal', '=', '0000-00-00 00:00:00');
                });
        })->with('header_penerimaan')
            ->get();

        $data = array(
            'transaksi_pendapatan' => $transaksiPendapatan,
            'penerimaan' => $penerimaan,
            'penerimaan2' => $penerimaan2
        );
        return response()->json($data);
    }
}

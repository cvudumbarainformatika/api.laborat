<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanPenerimaanController extends Controller
{
    public function lappenerimaan()
    {
        $judulsatu = Penerimaan::select('substring_index(kode_50,' . ',4)')
            ->groupby('substring_index(kode_50,' . ',4)')->get();
        return new JsonResponse($judulsatu);
    }
}

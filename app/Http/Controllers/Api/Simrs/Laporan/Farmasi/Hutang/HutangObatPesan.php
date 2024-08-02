<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HutangObatPesan extends Controller
{
    public function reportObatPesananBytanggal()
    {
        $dari = request('tgldari');
        $data = PenerimaanHeder::where('tglpenerimaan','<=', $dari)
        ->where('tgl_pembayaran','>=', $dari)
        ->where('jenis_penerimaan','Pesanan')
        ->get();
        return new JsonResponse($data);
    }
}

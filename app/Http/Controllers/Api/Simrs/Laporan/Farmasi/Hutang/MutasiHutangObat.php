<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MutasiHutangObat extends Controller
{
    public function reportMutasiHutangObat()
    {
        $dari = request('tgldari');
        $tglsampai = request('tglsampai');
        $saldoawal = PenerimaanHeder::whereDate('tglpenerimaan','<=', $dari)
        ->with(
            [
                'pihakketiga',
                'penerimaanrinci' => function($penerimaanrinci){
                    $penerimaanrinci->with('masterobat');
                }
            ]
        )
        ->where('jenis_penerimaan','Pesanan')
       // ->limit(10)
        ->get();

        return new JsonResponse($saldoawal);
    }
}

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
        $data = PenerimaanHeder::whereDate('tglpenerimaan','<=', $dari)
        ->with(
            [
                'pihakketiga',
                'penerimaanrinci' => function($penerimaanrinci){
                    $penerimaanrinci->with('masterobat');
                }
            ]
        )
        ->where('jenis_penerimaan','Pesanan')
        ->limit(20)
        ->get();
        return new JsonResponse($data);
    }
}

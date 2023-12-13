<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BastController extends Controller
{
    public function dialogsp()
    {
        $sp = PemesananHeder::with(
            [
                'rinci',
                'pihakketiga'
            ]
        )
            ->where('flag', '1')
            ->get();
        return new JsonResponse($sp);
    }

    public function dialogpenerimaan()
    {
        $dialogpenerimaan = PenerimaanHeder::with(
            [
                'penerimaanrinci',
                'penerimaanrinci.masterobat'
            ]
        )
            ->where('kunci', '1')
            ->where('nopemesanan', '!=', '')
            ->get();
        return new JsonResponse($dialogpenerimaan);
    }
}

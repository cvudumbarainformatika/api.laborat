<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StokrealController extends Controller
{
    public static function stokreal($nopenerimaan, $request)
    {
        //return ($request->kdobat);
        $simpanstokreal = Stokrel::updateOrCreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'kdobat' => $request->kdobat,
                'kdruang' => $request->kdruang
            ],
            [
                'tglpenerimaan' => $request->tanggal,
                'jumlah' => $request->jumlah,
                'harga' => $request->harga_kcl
            ]
        );
        if (!$simpanstokreal) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }
        return 200;
    }
}

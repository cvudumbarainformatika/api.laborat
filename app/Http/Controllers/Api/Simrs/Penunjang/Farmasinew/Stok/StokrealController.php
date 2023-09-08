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
                'kdruang' => $request->kdruang,
                'nobatch' => $request->no_batch,
                'tglexp' => $request->tgl_exp,
                'harga' => $request->harga_kcl
            ],
            [
                'tglpenerimaan' => $request->tglpenerimaan,
                'jumlah' => $request->jumlah,
                'flag' => 1

            ]
        );
        if (!$simpanstokreal) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }
        return 200;
    }
}

<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LihatStokController extends Controller
{

    public function lihatstokobateresep()
    {
        $sistembayar = request('groups');
        $cariobat = Mobatnew::with(
            [
                'stokrealallrs'
            ]
        )
            ->where('sistembayar', $sistembayar)
            ->get();

        return new JsonResponse($cariobat);
    }
}

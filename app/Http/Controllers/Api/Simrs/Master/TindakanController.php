<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TindakanController extends Controller
{
    public function listtindakan()
    {
        $listtindakan = Mtindakan::select(
            'rs1 as kodetindakan',
            'rs2 as nmtindkan',
            'rs8 as js3',
            'rs9 as jp3',
            DB::raw('rs8+rs9 as tarif3'),
            'rs11 as js2',
            'rs12 as jp2',
            DB::raw('rs11+rs12 as tarif2'),
            'rs14 as js1',
            'rs15 as jp1',
            DB::raw('rs14+rs15 as tarif1'),
            'rs17 as jsutama',
            'rs18 as jputama',
            DB::raw('rs17+rs18 as tarifutama'),
            'rs20 as jsvip',
            'rs21 as jpvip',
            DB::raw('rs20+rs21 as tarifvip'),
            'rs23 as jsvvip',
            'rs24 as jpvvip',
            DB::raw('rs23+rs24 as tarifvvip')
        )
            ->paginate(request('per_page'));
        return new JsonResponse($listtindakan);
    }

    public function simpanmastertindakan(Request $request)
    {
        $ceknama = Mtindakan::where('rs2', 'like', '%' . $request->nmtindakan . '%')->count();
        if ($ceknama > 0) {
            return new JsonResponse(['message' => 'Maaf Tindakan Sudah Ada...!!!']);
        }

        $simpantindakan = Mtindakan::updateOrCreate(
            [
                ''
            ]
        );
    }
}

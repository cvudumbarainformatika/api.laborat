<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TindakanController extends Controller
{
    public function listtindakan()
    {
        $listtindakan = Mtindakan::select(
            'rs1 as kodetindakan',
            'rs2 as nmtindkan',
            'rs8 as js3',
            'rs9 as jp3',
            'rs11 as js2',
            'rs12 as jp2',
            'rs14 as js1',
            'rs15 as jp1',
            'rs17 as jsutama',
            'rs18 as jputama',
            'rs20 as jsvip',
            'rs23 as jpvvip',
            'rs24 as jpvvip'
        )
            ->paginate(request('perpage'));
        return new JsonResponse($listtindakan);
    }
}

<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Diagnosa_m;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosaController extends Controller
{
    public function listdiagnosa()
    {
        $listdiagnosa = Diagnosa_m::where('disable_status', '')->orderBy('rs3')->limit(25)->get();
        return new JsonResponse($listdiagnosa);
    }
}

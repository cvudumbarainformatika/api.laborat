<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Diagnosa_m;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListdiagnosaController extends Controller
{
    public function listdiagnosa()
    {
        $listdiagnosa = Diagnosa_m::select('rs1 as kode', 'rs4 as keterangan')
            ->where('rs1', 'Like', '%' . request('diagnosa') . '%')
            ->orWhere('rs4', 'Like', '%' . request('diagnosa') . '%')
            ->get();
        return new JsonResponse($listdiagnosa);
    }

    public function simpandiagnosa(Request $request)
    {
        //$simpandiagnosa = ;
    }
}

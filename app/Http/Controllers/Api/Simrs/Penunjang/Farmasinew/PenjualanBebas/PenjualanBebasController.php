<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenjualanBebas;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenjualanBebasController extends Controller
{
    public function getPihakTiga(){
        $data=Mpihakketiga::select('nama', 'kode')
        ->where('nama', 'LIKE', '%' . request('q') . '%')
        ->limit(30)
        ->get();
        return new JsonResponse($data);
    }
}

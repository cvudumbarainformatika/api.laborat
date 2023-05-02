<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Msistembayar;
use Illuminate\Http\JsonResponse;


class SistemBayarController extends Controller
{
    public function index()
    {
        $data = Msistembayar::query()
        ->selectRaw('rs1 as kode,rs2 as groupsistembayar')
        ->where('rs3', '=' ,'')
        ->get();
        return new JsonResponse($data);
    }

}

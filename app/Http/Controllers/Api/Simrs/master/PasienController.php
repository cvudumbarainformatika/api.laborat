<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpasienx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public function index()
    {
        $query = Mpasien::query()
        ->selectRaw('rs1 as norm,rs2 as nama,rs3 as sapaan,rs4 as alamat,rs5 as kelurahan,rs6 as kota')
        ->limit(100);

        $queryx = Mpasienx::query()
        ->selectRaw('rs1 as norm,rs2 as nama,rs3 as sapaan,rs4 as alamat,rs5 as kelurahan,rs6 as kota')
        ->limit(100)
        ->unionAll($query)
        ->get();

        return new JsonResponse($queryx);

    }
}

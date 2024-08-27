<?php

namespace App\Http\Controllers\Api\Simrs\Rekammedik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Rekom\Rekomdpjp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingController extends Controller
{
    public function index()
    {
        $rs30 = Mtindakan::select('rs1 as kode', 'rs2 as nama')

            ->get();

        return new JsonResponse($rs30, 200);
    }
}

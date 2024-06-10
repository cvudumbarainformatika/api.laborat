<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Ambulan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Ambulan\TujuanAmbulan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmbulanController extends Controller
{
    public function getTujuanAmbulan() {
        $tujuan = TujuanAmbulan::where('flag','')->get();
        return new JsonResponse($tujuan);
    }
}

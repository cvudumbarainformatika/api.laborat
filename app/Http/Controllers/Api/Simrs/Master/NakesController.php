<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Dokter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NakesController extends Controller
{
    public function selaindokter()
    {
        $selaindokter = Dokter::where('rs13','!=', '1')->where('rs1','!=','')
        ->get();
        return new JsonResponse($selaindokter);
    }
}

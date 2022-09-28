<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\PemeriksaanLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemeriksaanLaboratController extends Controller
{
    public function groupped()
    {
        $query = collect(PemeriksaanLaborat::all());
        $data= $query->groupBy('rs21');

        return new JsonResponse($data);
    }
}

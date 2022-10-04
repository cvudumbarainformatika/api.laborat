<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\TransaksiLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardLaboratController extends Controller
{
    public function index()
    {

        $lab = TransaksiLaborat::selectRaw('COUNT(rs2) as y, DATE(rs3) as x')
        ->groupBy('x')
        ->whereMonth('rs3', '=', '10')
        ->whereYear('rs3', '=', '2020')
        ->orderBy('rs3', 'desc')->get();

        $lab_luar = LaboratLuar::selectRaw('COUNT(nota) as y, DATE(tgl) as x')
        ->groupBy('x')
        ->whereMonth('tgl', '=', '10')
        ->whereYear('tgl', '=', '2020')
        ->orderBy('tgl', 'desc')->get();

        $data = array(
            'lab'=> $lab,
            'lab_luar'=> $lab_luar
        );

        return new JsonResponse($data);
    }
}

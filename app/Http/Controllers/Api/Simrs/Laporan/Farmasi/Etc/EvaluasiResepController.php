<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Etc;

use App\Http\Controllers\Controller;
use App\Services\LaporanResepService;
use Illuminate\Http\Request;

class EvaluasiResepController extends Controller
{
    //
    public function index()
    {
        $params = request()->all();
        return response()->json(LaporanResepService::getLaporan($params));
    }
}

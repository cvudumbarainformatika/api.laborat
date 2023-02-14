<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use App\Models\Agama;
use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeuanganController extends Controller
{
    public function pendapatan()
    {
        $data = KeuTransPendapatan::where('noTrans', 'not like', "%TBP-UJ%")
            ->whereMonth('tgl', request('month'))
            ->whereYear('tgl', request('year'))->get();
        return response()->json($data);
    }
}

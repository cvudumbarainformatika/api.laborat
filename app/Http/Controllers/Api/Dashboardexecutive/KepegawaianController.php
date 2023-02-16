<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KepegawaianController extends Controller
{
    public function index()
    {
        $struktural = DB::connection('kepex')->select(
            "select jenispegawai,count(nip) jumlah from pegawai p where jenispegawai='Struktural' and aktif='AKTIF'"
        );

        $data = array(
            'struktural' => $struktural,
        );
        return response()->json($struktural);
    }
}

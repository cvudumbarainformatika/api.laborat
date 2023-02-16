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
            "select p.kategoripegawai kode_kat,namakategoripeg,count(nip) jumlah
		            from pegawai p left join m_kategori_peg k on p.kategoripegawai=k.kodekategoripeg
                    where aktif='AKTIF' and jenispegawai!='Struktural' group by kategoripegawai order by k.kodekategoripeg"
        );

        $data = array(
            'struktural' => $struktural,
        );
        return response()->json($struktural);
    }
}

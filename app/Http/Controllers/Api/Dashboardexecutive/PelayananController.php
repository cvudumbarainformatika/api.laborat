<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelayananController extends Controller
{
    public function index()
    {
        $data = DB::select(
            "SELECT * FROM (
                    SELECT UPPER(rs24.rs2) AS ruang,COUNT(vBed.rs5) AS total,SUM(vBed.terisi) AS terisi,( COUNT(vBed.rs5) - SUM(vBed.terisi) ) AS sisa FROM (
                    SELECT rs5,IF(rs3='S',1,0) AS terisi FROM rs25 WHERE rs7<>'1' AND extra<>'1' AND rs5<>'-' AND rs8<>'1'
                    ) AS vBed,rs24
                    WHERE rs24.rs1=vBed.rs5 AND rs24.status<>'1' AND rs24.rs4<>'BR' GROUP BY vBed.rs5
                    UNION ALL
                    SELECT UPPER(ruang) AS ruang,COUNT(ruang) AS total,SUM(terisi) AS terisi,(COUNT(ruang)-SUM(terisi)) AS sisa FROM (
                        SELECT CONCAT('Ruang ',rs1) AS ruang,IF(rs3='S',1,0) AS terisi FROM rs25 WHERE rs7<>'1' AND extra<>'1' AND rs5='-' AND rs8<>'1' AND rs6<>'BR'
                        ) AS vBed GROUP BY ruang
                        ) AS vKamar ORDER BY ruang ASC"
        );
        return response()->json($data);
    }
}

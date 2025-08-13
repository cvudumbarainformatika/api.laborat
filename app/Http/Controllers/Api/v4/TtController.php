<?php

namespace App\Http\Controllers\Api\v4;

// use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TtController extends Controller
{
    public function index()
    {
        $data = DB::select("
            SELECT * FROM (
                SELECT 
                    UPPER(rs24.rs2) AS ruang,
                    rs24.jenis,
                    COUNT(vBed.rs5) AS total,
                    SUM(vBed.terisi) AS terisi,
                    ( COUNT(vBed.rs5) - SUM(vBed.terisi) ) AS sisa
                FROM (
                    SELECT rs5, IF(rs3='S',1,0) AS terisi 
                    FROM rs25 
                    WHERE rs7<>'1' AND extra<>'1' AND rs5<>'-' AND rs8<>'1'
                ) AS vBed
                JOIN rs24 ON rs24.rs1 = vBed.rs5 
                WHERE rs24.status<>'1' AND rs24.rs4<>'BR' 
                GROUP BY vBed.rs5

                UNION ALL

                SELECT 
                    UPPER(ruang) AS ruang,
                    NULL AS jenis,  -- supaya jumlah kolom dan tipe konsisten
                    COUNT(ruang) AS total,
                    SUM(terisi) AS terisi,
                    (COUNT(ruang)-SUM(terisi)) AS sisa 
                FROM (
                    SELECT CONCAT('Ruang ',rs1) AS ruang, IF(rs3='S',1,0) AS terisi 
                    FROM rs25 
                    WHERE rs7<>'1' AND extra<>'1' AND rs5='-' AND rs8<>'1' AND rs6<>'BR'
                ) AS vBed 
                GROUP BY ruang
            ) AS vKamar 
            ORDER BY ruang ASC;
        ");

        $data = array(
            "tempat_tidur" => $data,
        );
        return response()->json($data);
    }
}

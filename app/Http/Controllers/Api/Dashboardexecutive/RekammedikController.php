<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekammedikController extends Controller
{
    public function index()
    {
        $m = request('month');
        $y = request('year');
        $d = request('d');
        $tglF = $y . '-' . $m . '-' . $d;
        $time = strtotime($tglF);

        $tgl = date('Y-m-d', $time);

        $periode1 = $y . '-' . '01' . '-' . '01';
        $periode2 = $y . '-' . '12' . '-' . '31';

        $topicd10ranap = DB::select("
            SELECT
                rs23.rs26 AS icd,
                rs99x.rs4 AS keterangan,
                COUNT(rs23.rs1) AS jumlah
            FROM rs15
            JOIN rs23 ON rs23.rs2 = rs15.rs1
            JOIN rs99x ON rs99x.rs1 = rs23.rs26
            JOIN rs9 ON rs9.rs1 = rs23.rs19
            WHERE rs23.rs26 NOT IN ('Z38.0', 'O82.1', 'O82', 'O80.9')
            AND rs23.rs4 BETWEEN ? AND ?
            AND rs23.rs26 NOT LIKE '%o26%'
            GROUP BY rs23.rs26
            ORDER BY jumlah DESC
            LIMIT 10
        ", [$periode1 . ' 00:00:00', $periode2 . ' 23:59:59']);

        $topicd10rajal = DB::select("
            SELECT
                rs101.rs3 AS icd,
                rs99x.rs4 AS keterangan,
                COUNT(rs101.rs3) AS jumlah
            FROM rs15
            JOIN rs17 ON rs17.rs2 = rs15.rs1
            left join rs101 on rs101.rs1 = rs17.rs1
            JOIN rs99x ON rs99x.rs1 = rs101.rs3
            WHERE rs101.rs3 NOT IN ('Z38.0', 'O82.1', 'O82', 'O80.9')
            AND rs17.rs3 BETWEEN ? AND ?
            AND rs101.rs3 NOT LIKE '%o26%'
            and rs17.rs8<>'POL014' and rs17.rs8<>'POL005'
            and rs101.rs3<>'Z09.8' and rs101.rs3<>'Z09.4' and rs101.rs3<>'Z00.0' and rs101.rs3<>'O82'
            GROUP BY rs101.rs3
            ORDER BY jumlah DESC
            LIMIT 10
        ", [$periode1 . ' 00:00:00', $periode2 . ' 23:59:59']);

        $topicd10igd = DB::select("
            SELECT
                rs101.rs3 AS icd,
                rs99x.rs4 AS keterangan,
                COUNT(rs101.rs3) AS jumlah
            FROM rs15
            JOIN rs17 ON rs17.rs2 = rs15.rs1
            left join rs101 on rs101.rs1 = rs17.rs1
            JOIN rs99x ON rs99x.rs1 = rs101.rs3
            WHERE rs101.rs3 NOT IN ('Z38.0', 'O82.1', 'O82', 'O80.9')
            AND rs17.rs3 BETWEEN ? AND ?
            AND rs101.rs3 NOT LIKE '%o26%'
            and rs17.rs8='POL014'
            GROUP BY rs101.rs3
            ORDER BY jumlah DESC
            LIMIT 10
        ", [$periode1 . ' 00:00:00', $periode2 . ' 23:59:59']);

         $borlostoy = DB::select("
            select * from borsemen where ruangan='rs' and year(tgl)='$y'
        ");

        $data = array(
            'topicd10ranap' => $topicd10ranap,
            'topicd10rajal' => $topicd10rajal,
            'topicd10igd' => $topicd10igd,
            'borlostoy' => $borlostoy,
        );
        return response()->json($data);
    }
}

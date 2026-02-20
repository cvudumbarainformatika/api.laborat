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
        SELECT
            UPPER(r.rs2) AS ruang,
            r.jenis,
    
            COUNT(b.id) AS total,
    
            /* TERISI (BED NORMAL SAJA) */
            SUM(
                CASE
                    WHEN b.rs8 <> '1'
                     AND EXISTS (
                        SELECT 1
                        FROM v_15_23 v
                        JOIN rs23 p ON p.rs1 = v.noreg
                        WHERE v.kd_kmr = b.rs1
                          AND v.no_bed = b.rs2
                          AND v.status_inap = ''
                          AND (
                                v.kd_kelas = r.rs1
                                OR p.titipan = r.rs1
                              )
                    )
                    THEN 1 ELSE 0
                END
            ) AS terisi,
    
            /* RUSAK */
            SUM(CASE WHEN b.rs8 = '1' THEN 1 ELSE 0 END) AS rusak,
    
            /* SISA */
            (
                COUNT(b.id)
                -
                SUM(
                    CASE
                        WHEN b.rs8 <> '1'
                         AND EXISTS (
                            SELECT 1
                            FROM v_15_23 v
                            JOIN rs23 p ON p.rs1 = v.noreg
                            WHERE v.kd_kmr = b.rs1
                              AND v.no_bed = b.rs2
                              AND v.status_inap = ''
                              AND (
                                    v.kd_kelas = r.rs1
                                    OR p.titipan = r.rs1
                                  )
                        )
                        THEN 1 ELSE 0
                    END
                )
                -
                SUM(CASE WHEN b.rs8 = '1' THEN 1 ELSE 0 END)
            ) AS sisa
    
            FROM rs25 b
            JOIN rs24 r ON r.rs1 = b.rs5
        
            WHERE b.rs7 <> 1
            AND r.status <> '1'
            AND r.hiddens <> '1'
        
            GROUP BY r.rs1, r.rs2, r.jenis
            ORDER BY ruang ASC
        ");

        $data = array(
            "tempat_tidur" => $data,
        );
        return response()->json($data);
    }
}

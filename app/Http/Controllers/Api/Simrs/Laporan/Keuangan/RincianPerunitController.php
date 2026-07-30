<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RincianPerunitController extends Controller
{
    public function rincianperunit(Request $request)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'pelayanan' => ['required', 'integer'],
            'jenisLaporan' => ['nullable', 'integer'],
        ]);

        $pelayanan = (int) $validated['pelayanan'];
        $jenisLaporan = (int) ($validated['jenisLaporan'] ?? 0);

        if ($pelayanan === 2 && $jenisLaporan === 2) {
            return $this->tindakanIgd($validated['from'], $validated['to']);
        }

        if ($pelayanan === 3 && $jenisLaporan === 3) {
            return $this->tindakanRanap($validated['from'], $validated['to']);
        }

        return response()->json([
            'message' => 'Laporan yang dipilih masih dalam proses.',
            'data' => null,
        ], 422);
    }

    private function tindakanIgd(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs73.rs1 AS noreg,
                    rs17.rs3 AS tglMasuk,
                    rs17.rs3 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    rs19.rs2 AS ruang,
                    rs21.rs2 AS dokter,
                    tpelaksana.rs2 AS pelaksana,
                    rs9.rs2 AS sistemBayar,
                    rs73.rs3 AS tglTrans,
                    rs30.rs2 AS namaTrans,
                    rs73.rs5 AS jml,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND(rs73.rs13 * rs73.rs5),
                        0
                    ) AS potjas,
                    ROUND(rs73.rs7) AS sarana,
                    ROUND(rs73.rs13) AS pelayanan,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND(((rs73.rs7 + rs73.rs13) * rs73.rs5) - (rs73.rs13 * rs73.rs5)),
                        ROUND((rs73.rs7 + rs73.rs13) * rs73.rs5)
                    ) AS subtotal
                FROM rs73
                LEFT JOIN potongan_jasa
                    ON rs73.id = potongan_jasa.id_trans
                    AND potongan_jasa.jenis = 'tindakan_igd'
                INNER JOIN rs30 ON rs73.rs4 = rs30.rs1
                INNER JOIN rs17 ON rs73.rs1 = rs17.rs1
                INNER JOIN rs9 ON rs17.rs14 = rs9.rs1
                INNER JOIN rs15 ON rs17.rs2 = rs15.rs1
                INNER JOIN rs21 AS tpelaksana ON rs73.rs8 = tpelaksana.rs1
                INNER JOIN rs19 ON rs17.rs8 = rs19.rs1
                INNER JOIN rs21 ON rs17.rs9 = rs21.rs1
                WHERE rs73.rs22 = 'POL014'
                    AND rs17.rs19 = '1'
                    AND rs17.rs3 >= ?
                    AND rs17.rs3 < ?
                ORDER BY rs17.rs3, rs73.rs3, rs73.rs1
            SQL,
            [$from, $toExclusive]
        );

        $columns = [
            'noreg' => 'No.Reg',
            'tglMasuk' => 'Tgl Masuk',
            'tglKeluar' => 'Tgl Keluar',
            'norm' => 'No.RM',
            'namaPasien' => 'Nama Pasien',
            'ruang' => 'Ruang',
            'dokter' => 'Dokter Utama',
            'pelaksana' => 'Pelaksana',
            'sistemBayar' => 'Sistem Bayar',
            'tglTrans' => 'Tgl Transaksi',
            'namaTrans' => 'Nama Transaksi',
            'jml' => 'Jumlah',
            'potjas' => 'Potongan Jasa',
            'sarana' => 'Sarana',
            'pelayanan' => 'Pelayanan',
            'subtotal' => 'Subtotal',
        ];

        return response()->json([
            'Title' => 'Rincian Tindakan IGD',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ]);
    }

    private function tindakanRanap(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs73.rs1 AS noreg,
                    rs23.rs3 AS tglMasuk,
                    rs23.rs4 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    v_gudang.rs2 AS ruang,
                    rs21.rs2 AS dokter,
                    rs73.rs8 AS pelaksana1,
                    rs73.rs23 AS pelaksana2,
                    rs9.rs2 AS sistemBayar,
                    rs73.rs3 AS tglTrans,
                    rs30.rs2 AS namaTrans,
                    rs73.rs5 AS jml,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND(rs73.rs13 * rs73.rs5),
                        0
                    ) AS potjas,
                    ROUND(rs73.rs7) AS sarana,
                    ROUND(rs73.rs13) AS pelayanan,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND(((rs73.rs7 + rs73.rs13) * rs73.rs5) - (rs73.rs13 * rs73.rs5)),
                        ROUND((rs73.rs7 + rs73.rs13) * rs73.rs5)
                    ) AS subtotal
                FROM rs73
                LEFT JOIN potongan_jasa
                    ON rs73.id = potongan_jasa.id_trans
                    AND potongan_jasa.jenis = 'tindakan_ranap'
                INNER JOIN rs23 ON rs73.rs1 = rs23.rs1
                INNER JOIN rs15 ON rs23.rs2 = rs15.rs1
                INNER JOIN v_gudang ON rs73.rs22 = v_gudang.rs1
                INNER JOIN rs21 ON rs23.rs10 = rs21.rs1
                INNER JOIN rs30 ON rs73.rs4 = rs30.rs1
                INNER JOIN rs9 ON rs23.rs19 = rs9.rs1
                WHERE rs73.rs22 IN (
                    'BG', 'BR', 'DA', 'FA', 'IC', 'ICC', 'MA', 'ME',
                    'WK', 'WKUT', 'WKVVIP', 'WKKB', 'KA', 'ISHK', 'TR',
                    'SKR', 'ASK', 'TLP'
                )
                    AND rs23.rs22 <> ''
                    AND rs23.rs4 >= ?
                    AND rs23.rs4 < ?
                ORDER BY rs23.rs4, rs73.rs3, rs73.rs1
            SQL,
            [$from, $toExclusive]
        );

        $columns = [
            'noreg' => 'No.Reg',
            'tglMasuk' => 'Tgl Masuk',
            'tglKeluar' => 'Tgl Keluar',
            'norm' => 'No.RM',
            'namaPasien' => 'Nama Pasien',
            'ruang' => 'Ruang',
            'dokter' => 'Dokter Utama',
            'pelaksana1' => 'Pelaksana 1',
            'pelaksana2' => 'Pelaksana 2',
            'sistemBayar' => 'Sistem Bayar',
            'tglTrans' => 'Tgl Transaksi',
            'namaTrans' => 'Nama Transaksi',
            'jml' => 'Jumlah',
            'potjas' => 'Potongan Jasa',
            'sarana' => 'Sarana',
            'pelayanan' => 'Pelayanan',
            'subtotal' => 'Subtotal',
        ];

        return response()->json([
            'Title' => 'Rincian Tindakan Rawat Inap',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ]);
    }
}

<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RincianPerunitIgdService
{
    public static function get(int $jenisLaporan, string $from, string $to): array
    {
        $service = new self();

        if ($jenisLaporan === 1) {
            return $service->administrasiIgd($from, $to);
        }

        if ($jenisLaporan === 2) {
            return $service->tindakanIgd($from, $to);
        }

        abort(422, 'Jenis laporan IGD belum tersedia.');
    }

    private function administrasiIgd(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs35x.rs1 AS noreg,
                    rs17.rs3 AS tglMasuk,
                    rs17.rs3 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    rs19.rs2 AS ruang,
                    rs21.rs2 AS dokter,
                    rs9.rs2 AS sistemBayar,
                    rs35x.rs4 AS tglTrans,
                    rs35x.rs6 AS namaTrans,
                    ROUND(rs35x.rs7) AS sarana,
                    ROUND(rs35x.rs11) AS pelayanan,
                    ROUND(rs35x.rs7 + rs35x.rs11) AS subtotal
                FROM rs35x
                INNER JOIN rs17 ON rs35x.rs1 = rs17.rs1
                INNER JOIN rs9 ON rs17.rs14 = rs9.rs1
                INNER JOIN rs15 ON rs17.rs2 = rs15.rs1
                INNER JOIN rs19 ON rs17.rs8 = rs19.rs1
                INNER JOIN rs21 ON rs17.rs9 = rs21.rs1
                WHERE rs35x.rs3 = 'A2#'
                    AND rs17.rs19 = '1'
                    AND rs17.rs3 >= ?
                    AND rs17.rs3 < ?
                ORDER BY rs17.rs3, rs35x.rs4, rs35x.rs1
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
            'sistemBayar' => 'Sistem Bayar',
            'tglTrans' => 'Tgl Transaksi',
            'namaTrans' => 'Nama Transaksi',
            'sarana' => 'Sarana',
            'pelayanan' => 'Pelayanan',
            'subtotal' => 'Subtotal',
        ];

        return [
            'Title' => 'Rincian Administrasi IGD',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
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

        return [
            'Title' => 'Rincian Tindakan IGD',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }
}

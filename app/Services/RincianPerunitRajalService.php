<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RincianPerunitRajalService
{
    public static function get(int $jenisLaporan, string $from, string $to): array
    {
        $service = new self();

        if ($jenisLaporan === 1) {
            return $service->laporanRawatJalan($from, $to, 'K2#', 'Rincian Poli Klinik Spesialis');
        }

        if ($jenisLaporan === 2) {
            return $service->laporanRawatJalan($from, $to, 'K3#', 'Rincian Konsultasi Antar Poli');
        }

        if ($jenisLaporan === 3) {
            return $service->tindakanRawatJalan($from, $to);
        }

        abort(422, 'Jenis laporan Rawat Jalan belum tersedia.');
    }

    private function laporanRawatJalan(
        string $from,
        string $to,
        string $kodeTransaksi,
        string $title
    ) {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs35.rs1 AS noreg,
                    rs17.rs3 AS tglMasuk,
                    rs17.rs3 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    rs19.rs2 AS ruang,
                    rs21.rs2 AS dokter,
                    rs9.rs2 AS sistemBayar,
                    rs35.rs4 AS tglTrans,
                    rs35.rs6 AS namaTrans,
                    ROUND(rs35.rs7) AS sarana,
                    ROUND(rs35.rs11) AS pelayanan,
                    ROUND(rs35.rs7 + rs35.rs11) AS subtotal
                FROM rs35
                INNER JOIN rs17 ON rs35.rs1 = rs17.rs1
                INNER JOIN rs9 ON rs17.rs14 = rs9.rs1
                INNER JOIN rs15 ON rs17.rs2 = rs15.rs1
                INNER JOIN rs19 ON rs17.rs8 = rs19.rs1
                INNER JOIN rs21 ON rs17.rs9 = rs21.rs1
                WHERE rs35.rs3 = ?
                    AND rs17.rs19 = '1'
                    AND rs17.rs3 >= ?
                    AND rs17.rs3 < ?
                ORDER BY rs17.rs3, rs35.rs4, rs35.rs1
            SQL,
            [$kodeTransaksi, $from, $toExclusive]
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
            'Title' => $title,
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }

    private function tindakanRawatJalan(string $from, string $to)
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
                    tpelaksana.nama AS pelaksana,
                    rs9.rs2 AS sistemBayar,
                    rs73.rs3 AS tglTrans,
                    rs30.rs2 AS namaTrans,
                    rs73.rs5 AS jml,
                    ROUND(rs73.rs7 * rs73.rs5) AS sarana,
                    ROUND(rs73.rs13 * rs73.rs5) AS pelayanan,
                    ROUND((rs73.rs7 + rs73.rs13) * rs73.rs5) AS subtotal
                FROM rs73
                LEFT JOIN rs17 ON rs17.rs1 = rs73.rs1
                LEFT JOIN rs21 ON rs73.rs8 = rs21.rs1
                LEFT JOIN kepegx.pegawai AS tpelaksana
                    ON rs73.rs9 = tpelaksana.kdpegsimrs
                LEFT JOIN rs30 ON rs73.rs4 = rs30.rs1
                LEFT JOIN rs15 ON rs17.rs2 = rs15.rs1
                LEFT JOIN rs19 ON rs17.rs8 = rs19.rs1
                LEFT JOIN rs9 ON rs17.rs14 = rs9.rs1
                WHERE rs73.rs22 NOT IN (
                    'OPERASI', 'FISIO', 'POL024', 'POL026',
                    'PEN005', 'POL031', 'POL014'
                )
                    AND rs17.rs8 <> 'POL014'
                    AND rs17.rs19 = '1'
                    AND rs17.rs3 >= ?
                    AND rs17.rs3 < ?
                GROUP BY rs73.rs2, rs73.rs4
                ORDER BY rs17.rs3
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
            'sarana' => 'Sarana',
            'pelayanan' => 'Pelayanan',
            'subtotal' => 'Subtotal',
        ];

        return [
            'Title' => 'Rincian Tindakan Rawat Jalan',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }
}

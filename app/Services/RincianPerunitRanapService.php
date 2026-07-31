<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RincianPerunitRanapService
{
    public static function get(int $jenisLaporan, string $from, string $to): array
    {
        $service = new self();

        $methods = [
            1 => 'administrasiRanap',
            2 => 'akomodasiKamar',
            3 => 'tindakanRanap',
            4 => 'visiteDokterRanap',
            5 => 'jasaKeperawatanRanap',
            6 => 'oksigenRanap',
        ];

        abort_unless(isset($methods[$jenisLaporan]), 422, 'Jenis laporan Rawat Inap belum tersedia.');

        return $service->{$methods[$jenisLaporan]}($from, $to);
    }

    private function administrasiRanap(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    v_adm.noreg,
                    v_adm.tglMasuk,
                    v_adm.tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    rs24.rs5 AS ruang,
                    rs21.rs2 AS dokter,
                    rs9.rs2 AS sistemBayar,
                    v_adm.tglTrans,
                    CONCAT(rs30tarif.rs2, ' ', rs24.rs2) AS namaTrans,
                    CASE
                        WHEN v_adm.kelas = '3' THEN ROUND(rs30tarif.rs6)
                        WHEN v_adm.kelas = '2' THEN ROUND(rs30tarif.rs8)
                        WHEN v_adm.kelas IN ('1', 'IC', 'ICC', 'NICU', 'IN') THEN ROUND(rs30tarif.rs10)
                        WHEN v_adm.kelas = 'Utama' THEN ROUND(rs30tarif.rs12)
                        WHEN v_adm.kelas = 'VIP' THEN ROUND(rs30tarif.rs14)
                        WHEN v_adm.kelas = 'VVIP' THEN ROUND(rs30tarif.rs16)
                        ELSE 0
                    END AS sarana,
                    CASE
                        WHEN v_adm.kelas = '3' THEN ROUND(rs30tarif.rs7)
                        WHEN v_adm.kelas = '2' THEN ROUND(rs30tarif.rs9)
                        WHEN v_adm.kelas IN ('1', 'IC', 'ICC', 'NICU', 'IN') THEN ROUND(rs30tarif.rs11)
                        WHEN v_adm.kelas = 'Utama' THEN ROUND(rs30tarif.rs13)
                        WHEN v_adm.kelas = 'VIP' THEN ROUND(rs30tarif.rs15)
                        WHEN v_adm.kelas = 'VVIP' THEN ROUND(rs30tarif.rs17)
                        ELSE 0
                    END AS pelayanan,
                    CASE
                        WHEN v_adm.kelas = '3' THEN ROUND(rs30tarif.rs6 + rs30tarif.rs7)
                        WHEN v_adm.kelas = '2' THEN ROUND(rs30tarif.rs8 + rs30tarif.rs9)
                        WHEN v_adm.kelas IN ('1', 'IC', 'ICC', 'NICU', 'IN')
                            THEN ROUND(rs30tarif.rs10 + rs30tarif.rs11)
                        WHEN v_adm.kelas = 'Utama' THEN ROUND(rs30tarif.rs12 + rs30tarif.rs13)
                        WHEN v_adm.kelas = 'VIP' THEN ROUND(rs30tarif.rs14 + rs30tarif.rs15)
                        WHEN v_adm.kelas = 'VVIP' THEN ROUND(rs30tarif.rs16 + rs30tarif.rs17)
                        ELSE 0
                    END AS subtotal
                FROM (
                    SELECT
                        rs35x.rs1 AS noreg,
                        rs23.rs3 AS tglMasuk,
                        rs23.rs4 AS tglKeluar,
                        rs23.rs2 AS norm,
                        rs35x.rs4 AS tglTrans,
                        rs35x.rs17 AS kelas,
                        rs23.rs10 AS kdDokter,
                        rs23.rs19 AS kdSistemBayar,
                        rs35x.rs18 AS kdRuang,
                        rs35x.rs3 AS kdTrans
                    FROM rs35x
                    INNER JOIN rs23 ON rs23.rs1 = rs35x.rs1
                    WHERE rs23.rs4 >= ?
                        AND rs23.rs4 < ?
                        AND rs23.rs22 <> ''
                        AND rs35x.rs3 = 'K1#'
                    ORDER BY rs35x.rs1, rs35x.rs4 DESC
                ) AS v_adm
                INNER JOIN rs15 ON v_adm.norm = rs15.rs1
                INNER JOIN rs9 ON v_adm.kdSistemBayar = rs9.rs1
                INNER JOIN rs21 ON v_adm.kdDokter = rs21.rs1
                INNER JOIN rs24 ON v_adm.kdRuang = rs24.rs1
                INNER JOIN rs30tarif ON rs30tarif.rs3 = 'A1#'
                GROUP BY v_adm.noreg
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
            'Title' => 'Rincian Administrasi Rawat Inap',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }

    private function akomodasiKamar(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs35x.rs1 AS noreg,
                    rs23.rs3 AS tglMasuk,
                    rs23.rs4 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    rs24.rs5 AS ruang,
                    rs21.rs2 AS dokter,
                    rs9.rs2 AS sistemBayar,
                    rs35x.rs4 AS tglTrans,
                    CONCAT(rs35x.rs6, ' ', rs24.rs2) AS namaTrans,
                    ROUND(rs35x.rs7) AS sarana,
                    ROUND(rs35x.rs14) AS pelayanan,
                    ROUND(rs35x.rs7 + rs35x.rs14) AS subtotal
                FROM rs35x
                INNER JOIN rs23 ON rs35x.rs1 = rs23.rs1
                INNER JOIN rs9 ON rs23.rs19 = rs9.rs1
                INNER JOIN rs15 ON rs23.rs2 = rs15.rs1
                INNER JOIN rs24 ON rs35x.rs18 = rs24.rs1
                INNER JOIN rs21 ON rs23.rs10 = rs21.rs1
                WHERE rs35x.rs3 = 'K1#'
                    AND rs23.rs22 <> ''
                    AND rs23.rs4 >= ?
                    AND rs23.rs4 < ?
                ORDER BY rs23.rs4, rs35x.rs4, rs35x.rs1
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
            'Title' => 'Rincian Akomodasi Kamar',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
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

        return [
            'Title' => 'Rincian Tindakan Rawat Inap',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }

    private function visiteDokterRanap(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs140.rs1 AS noreg,
                    rs23.rs3 AS tglMasuk,
                    rs23.rs4 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    v_gudang.rs2 AS ruang,
                    rs21.rs2 AS dokter,
                    pelaksana.rs2 AS pelaksana,
                    rs9.rs2 AS sistemBayar,
                    rs140.rs2 AS tglTrans,
                    rs30tarif.rs2 AS namaTrans,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND(rs140.rs5),
                        0
                    ) AS potjas,
                    ROUND(rs140.rs4) AS sarana,
                    ROUND(rs140.rs5) AS pelayanan,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND((rs140.rs4 + rs140.rs5) - rs140.rs5),
                        ROUND(rs140.rs4 + rs140.rs5)
                    ) AS subtotal
                FROM rs140
                LEFT JOIN potongan_jasa
                    ON rs140.id = potongan_jasa.id_trans
                    AND potongan_jasa.jenis = 'visite'
                INNER JOIN rs23 ON rs140.rs1 = rs23.rs1
                INNER JOIN rs15 ON rs23.rs2 = rs15.rs1
                INNER JOIN v_gudang ON rs140.rs8 = v_gudang.rs1
                INNER JOIN rs21 ON rs23.rs10 = rs21.rs1
                INNER JOIN rs30tarif ON rs140.rs6 = rs30tarif.rs3
                INNER JOIN rs21 AS pelaksana ON rs140.rs3 = pelaksana.rs1
                INNER JOIN rs9 ON rs23.rs19 = rs9.rs1
                WHERE rs23.rs22 <> ''
                    AND rs23.rs4 >= ?
                    AND rs23.rs4 < ?
                ORDER BY rs23.rs4, rs140.rs2, rs140.rs1
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
            'potjas' => 'Potongan Jasa',
            'sarana' => 'Sarana',
            'pelayanan' => 'Pelayanan',
            'subtotal' => 'Subtotal',
        ];

        return [
            'Title' => 'Rincian Visite/Konsultasi/Oncall Dokter Ranap',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }

    private function jasaKeperawatanRanap(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs203.rs1 AS noreg,
                    rs23.rs3 AS tglMasuk,
                    rs23.rs4 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    v_gudang.rs2 AS ruang,
                    rs21.rs2 AS dokter,
                    rs9.rs2 AS sistemBayar,
                    rs203.rs2 AS tglTrans,
                    rs30tarif.rs2 AS namaTrans,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND(rs203.rs5),
                        0
                    ) AS potjas,
                    ROUND(rs203.rs4) AS sarana,
                    ROUND(rs203.rs5) AS pelayanan,
                    IF(
                        potongan_jasa.id_trans IS NOT NULL,
                        ROUND((rs203.rs4 + rs203.rs5) - rs203.rs5),
                        ROUND(rs203.rs4 + rs203.rs5)
                    ) AS subtotal
                FROM rs203
                LEFT JOIN potongan_jasa
                    ON rs203.id = potongan_jasa.id_trans
                    AND potongan_jasa.jenis = 'visite'
                INNER JOIN rs23 ON rs203.rs1 = rs23.rs1
                INNER JOIN rs15 ON rs23.rs2 = rs15.rs1
                INNER JOIN v_gudang ON rs203.rs8 = v_gudang.rs1
                INNER JOIN rs21 ON rs23.rs10 = rs21.rs1
                INNER JOIN rs30tarif ON rs203.rs3 = rs30tarif.rs1
                INNER JOIN rs9 ON rs23.rs19 = rs9.rs1
                WHERE rs23.rs22 <> ''
                    AND rs23.rs4 >= ?
                    AND rs23.rs4 < ?
                ORDER BY rs23.rs4, rs203.rs2, rs203.rs1
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
            'potjas' => 'Potongan Jasa',
            'sarana' => 'Sarana',
            'pelayanan' => 'Pelayanan',
            'subtotal' => 'Subtotal',
        ];

        return [
            'Title' => 'Rincian Jasa Keperawatan Ranap',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }

    private function oksigenRanap(string $from, string $to)
    {
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    rs205.rs1 AS noreg,
                    rs23.rs3 AS tglMasuk,
                    rs23.rs4 AS tglKeluar,
                    CONCAT('&nbsp;', rs15.rs1) AS norm,
                    rs15.rs2 AS namaPasien,
                    v_gudang.rs2 AS ruang,
                    rs21.rs2 AS dokter,
                    rs9.rs2 AS sistemBayar,
                    rs205.rs2 AS tglTrans,
                    rs30tarif.rs2 AS namaTrans,
                    rs205.rs6 AS jml,
                    ROUND(rs205.rs4) AS sarana,
                    ROUND(rs205.rs5) AS pelayanan,
                    ROUND((rs205.rs4 + rs205.rs5) * rs205.rs6) AS subtotal
                FROM rs205
                INNER JOIN rs23 ON rs205.rs1 = rs23.rs1
                INNER JOIN rs9 ON rs23.rs19 = rs9.rs1
                INNER JOIN rs15 ON rs23.rs2 = rs15.rs1
                INNER JOIN v_gudang ON rs205.rs8 = v_gudang.rs1
                INNER JOIN rs21 ON rs23.rs10 = rs21.rs1
                INNER JOIN rs30tarif ON rs205.rs3 = rs30tarif.rs1
                WHERE rs23.rs22 <> ''
                    AND rs23.rs4 >= ?
                    AND rs23.rs4 < ?
                ORDER BY rs23.rs4, rs205.rs2, rs205.rs1
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
            'jml' => 'Jml',
            'sarana' => 'Sarana',
            'pelayanan' => 'Pelayanan',
            'subtotal' => 'Subtotal',
        ];

        return [
            'Title' => 'Rincian Oksigen Ranap',
            'Columns' => $columns,
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }
}

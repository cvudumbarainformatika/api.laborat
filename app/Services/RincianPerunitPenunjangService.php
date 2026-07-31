<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RincianPerunitPenunjangService
{
    public static function get(int $jenisLaporan, string $from, string $to): array
    {
        $fromStart = Carbon::parse($from)->startOfDay()->toDateTimeString();
        $toEnd = Carbon::parse($to)->endOfDay()->toDateTimeString();

        $reports = [
            1 => [
                'title' => 'Rincian Laborat',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'norm' => 'No.RM',
                    'namaPasien' => 'Nama Pasien',
                    'ruang' => 'Ruang',
                    'dokter' => 'Dokter Utama',
                    'sistemBayar' => 'Sistem Bayar',
                    'nota' => 'Nota',
                    'tglTrans' => 'Tgl Transaksi',
                    'namaTrans' => 'Nama Transaksi',
                    'jml' => 'Jml',
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    SELECT v_lab.noreg,v_lab.tglMasuk,v_lab.tglKeluar,CONCAT('&nbsp;',v_lab.norm) AS norm,rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,
                    		rs21.rs2 AS dokter,rs9.rs2 AS sistemBayar,v_lab.nota,v_lab.tglTrans,v_lab.namaTrans,v_lab.jml,v_lab.sarana,v_lab.pelayanan,v_lab.subtotal FROM (
                    		SELECT rs51.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,rs23.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs23.rs10 AS kdDokter,
                    		rs23.rs19 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs2 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                    		left join rs23 on rs51.rs1=rs23.rs1
                    		left join rs49 on rs51.rs4=rs49.rs1
                    		WHERE rs23.rs22<>'' AND rs23.rs4 BETWEEN ? AND ? AND rs49.rs21='' AND rs51.rs26='1'
                    		AND rs51.lunas<>'1' AND rs51.rs21<>'' AND rs51.rs23<>'POL014' and rs49.jenislab='PK'
                    		UNION ALL
                    		SELECT rs51.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,rs23.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs23.rs10 AS kdDokter,
                    		rs23.rs19 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs21 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                    		left join rs23 on rs51.rs1=rs23.rs1
                    		left join rs49 on rs51.rs4=rs49.rs1
                    		WHERE rs23.rs22<>'' AND rs23.rs4 BETWEEN ? AND ? AND rs49.rs21<>'' AND rs51.rs26='1'
                    		AND rs51.lunas<>'1' AND rs51.rs21<>'' AND rs51.rs23<>'POL014' and rs49.jenislab='PK' GROUP BY rs49.rs21,rs51.rs1,rs51.rs2
                    		UNION ALL
                    		SELECT rs51.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,rs17.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs17.rs9 AS kdDokter,
                    		rs17.rs14 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs2 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                    		left join rs17 on rs51.rs1=rs17.rs1
                    		left join rs49 on rs51.rs4=rs49.rs1
                    		WHERE rs17.rs19='1' AND rs17.rs3 BETWEEN ? AND ? AND rs49.rs21='' AND rs51.rs26='1'
                    		AND rs51.lunas<>'1' AND rs51.rs21<>'' AND rs51.rs23='POL014' and rs49.jenislab='PK'
                    		UNION ALL
                    		SELECT rs51.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,rs17.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs17.rs9 AS kdDokter,
                    		rs17.rs14 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs21 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                    		left join rs17 on rs51.rs1=rs17.rs1
                    		left join rs49 on rs51.rs4=rs49.rs1
                    		WHERE rs17.rs19='1' AND rs17.rs3 BETWEEN ? AND ? AND rs49.rs21<>'' AND rs51.rs26='1'
                    		AND rs51.lunas<>'1' AND rs51.rs21<>'' AND rs51.rs23='POL014' and rs49.jenislab='PK' GROUP BY rs49.rs21,rs51.rs1,rs51.rs2
                    		UNION ALL
                    		SELECT rs51.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,rs17.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs17.rs9 AS kdDokter,
                    		rs17.rs14 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs2 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                    		left join rs17 on rs51.rs1=rs17.rs1
                    		left join rs49 on rs51.rs4=rs49.rs1
                    		WHERE rs17.rs19='1' AND rs17.rs3 BETWEEN ? AND ?
                    		AND rs49.rs21='' AND rs51.rs26='1' AND rs51.rs23<>'POL014' AND rs51.rs23<>'BG' AND rs51.rs23<>'BR' AND rs51.rs23<>'DA'
                    		AND rs51.rs23<>'FA' AND rs51.rs23<>'IC' AND rs51.rs23<>'ICC' AND rs51.rs23<>'MA' AND rs51.rs23<>'ME'
                    		AND rs51.rs23<>'WK' AND rs51.rs23<>'WKUT' AND rs51.rs23<>'WKVVIP' AND rs51.rs23<>'KA' AND rs51.rs23<>'WKKB' AND rs51.rs23<>'ISHK' AND rs51.rs23<>'POL014'
                    		AND rs49.jenislab='PK'
                    		UNION ALL
                    		SELECT rs51.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,rs17.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs17.rs9 AS kdDokter,
                    		rs17.rs14 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs21 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                    		left join rs17 on rs51.rs1=rs17.rs1
                    		left join rs49 on rs51.rs4=rs49.rs1
                    		WHERE rs17.rs19='1' AND rs17.rs3 BETWEEN ? AND ? AND rs49.rs21<>'' AND rs51.rs26='1'
                    		AND rs51.rs23<>'BG' AND rs51.rs23<>'BR' AND rs51.rs23<>'DA'
                    		AND rs51.rs23<>'FA' AND rs51.rs23<>'IC' AND rs51.rs23<>'ICC' AND rs51.rs23<>'MA' AND rs51.rs23<>'ME'
                    		AND rs51.rs23<>'WK' AND rs51.rs23<>'WKUT' AND rs51.rs23<>'WKVVIP' AND rs51.rs23<>'KA' AND rs51.rs23<>'WKKB' AND rs51.rs23<>'ISHK' AND rs51.rs23<>'POL014'
                    		AND rs51.rs23<>'POL014' and rs49.jenislab='PK' GROUP BY rs49.rs21,rs51.rs1,rs51.rs2) AS v_lab,rs9,rs15,v_gudang,rs21 WHERE v_lab.kdSistemBayar=rs9.rs1 AND v_lab.norm=rs15.rs1
                    		AND v_lab.kdRuang=v_gudang.rs1 AND v_lab.kdDokter=rs21.rs1
                    		UNION ALL
                    		SELECT '' AS noreg,'' AS tglMasuk,'' AS tglKeluar,'' AS norm,lab_luar.nama AS namaPasien,
                    		'Permintaan Luar' AS ruang,lab_luar.pengirim AS dokter,
                    		'' AS sistemBayar,lab_luar.nota,lab_luar.tgl AS tglTrans,rs49.rs2 AS namaTrans,lab_luar.jml,
                    		ROUND(lab_luar.tarif_sarana) AS sarana,ROUND(lab_luar.tarif_pelayanan) AS pelayanan,
                    		ROUND((lab_luar.tarif_sarana+lab_luar.tarif_pelayanan)) AS subtotal FROM
                    		lab_luar
                    		left join rs49 on lab_luar.kd_lab=rs49.rs1
                    		WHERE lab_luar.tgl BETWEEN ? AND ? AND rs49.rs21='' and rs49.jenislab='PK'
                    		UNION ALL
                    		SELECT '' AS noreg,'' AS tglMasuk,'' AS tglKeluar,'' AS norm,lab_luar.nama AS namaPasien,
                    		'Permintaan Luar' AS ruang,lab_luar.pengirim AS dokter,
                    		'' AS sistemBayar,lab_luar.nota,lab_luar.tgl AS tglTrans,rs49.rs2 AS namaTrans,lab_luar.jml,
                    		ROUND(lab_luar.tarif_sarana) AS sarana,ROUND(lab_luar.tarif_pelayanan) AS pelayanan,
                    		ROUND((lab_luar.tarif_sarana+lab_luar.tarif_pelayanan)) AS subtotal FROM
                    		lab_luar
                    		left join rs49 on lab_luar.kd_lab=rs49.rs1
                    		WHERE lab_luar.kd_lab=rs49.rs1
                    		AND lab_luar.tgl BETWEEN ? AND ? AND rs49.rs21<>'' and rs49.jenislab='PK' GROUP BY rs49.rs21,lab_luar.nota
QUERY,
                'bindings' => [
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                ],
            ],
            2 => [
                'title' => 'Rincian Laborat PA',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'norm' => 'No.RM',
                    'namaPasien' => 'Nama Pasien',
                    'ruang' => 'Ruang',
                    'dokter' => 'Dokter Utama',
                    'sistemBayar' => 'Sistem Bayar',
                    'nota' => 'Nota',
                    'tglTrans' => 'Tgl Transaksi',
                    'namaTrans' => 'Nama Transaksi',
                    'jml' => 'Jml',
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    SELECT v_lab.noreg,v_lab.tglMasuk,v_lab.tglKeluar,CONCAT('&nbsp;',v_lab.norm) AS norm,rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,
                    		rs21.rs2 AS dokter,rs9.rs2 AS sistemBayar,v_lab.nota,v_lab.tglTrans,v_lab.namaTrans,v_lab.jml,v_lab.sarana,v_lab.pelayanan,v_lab.subtotal FROM (
                    		SELECT rs51.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,rs23.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs23.rs10 AS kdDokter,
                    		rs23.rs19 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs2 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                            left join rs23 on rs51.rs1=rs23.rs1
                            left join rs49 on rs51.rs4=rs49.rs1
                            WHERE rs23.rs22<>'' AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=? AND rs49.rs21='' AND rs51.rs26='1'
                    		AND rs51.lunas<>'1' AND rs51.rs21<>'' AND rs51.rs23<>'POL014' and rs49.jenislab='PA'
                    		UNION ALL
                    		SELECT rs51.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,rs17.rs2 AS norm,
                    		rs51.rs23 AS kdRuang,rs17.rs9 AS kdDokter,
                    		rs17.rs14 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs2 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                            left join rs17 on rs51.rs1=rs17.rs1
                            left join rs49 on rs51.rs4=rs49.rs1
                            WHERE rs17.rs19='1' AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=? AND rs49.rs21='' AND rs51.rs26='1'
                    		AND rs51.lunas<>'1' AND rs51.rs21<>'' AND rs51.rs23='POL014' and rs49.jenislab='PA'
                    		UNION ALL
                    		SELECT rs51.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,rs17.rs2 AS norm,
                    		rs17.rs8 AS kdRuang,rs17.rs9 AS kdDokter,
                    		rs17.rs14 AS kdSistemBayar,rs51.rs2 AS nota,rs51.rs3 AS tglTrans,rs49.rs2 AS namaTrans,rs51.rs5 AS jml,
                    		ROUND(rs51.rs6) AS sarana,ROUND(rs51.rs13) AS pelayanan,ROUND((rs51.rs6+rs51.rs13)) AS subtotal FROM
                    		rs51
                            left join rs17 on rs51.rs1=rs17.rs1
                            left join rs49 on rs51.rs4=rs49.rs1
                            WHERE rs17.rs19='1' AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		and rs49.jenislab='PA' AND rs17.rs8<>'POL014'
                    		) AS v_lab,rs9,rs15,v_gudang,rs21 WHERE v_lab.kdSistemBayar=rs9.rs1 AND v_lab.norm=rs15.rs1
                    		AND v_lab.kdRuang=v_gudang.rs1 AND v_lab.kdDokter=rs21.rs1
                    		UNION ALL
                    		SELECT '' AS noreg,'' AS tglMasuk,'' AS tglKeluar,'' AS norm,lab_luar.nama AS namaPasien,
                    		'Permintaan Luar' AS ruang,lab_luar.pengirim AS dokter,
                    		'' AS sistemBayar,lab_luar.nota,lab_luar.tgl AS tglTrans,rs49.rs2 AS namaTrans,lab_luar.jml,
                    		ROUND(lab_luar.tarif_sarana) AS sarana,ROUND(lab_luar.tarif_pelayanan) AS pelayanan,
                    		ROUND((lab_luar.tarif_sarana+lab_luar.tarif_pelayanan)) AS subtotal FROM
                    		lab_luar
                            left join rs49 on lab_luar.kd_lab=rs49.rs1
                    		WHERE DATE(lab_luar.tgl)>=? AND DATE(lab_luar.tgl)<=? AND rs49.rs21='' and rs49.jenislab='PA'
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            3 => [
                'title' => 'Rincian Radiologi',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'norm' => 'No.RM',
                    'namaPasien' => 'Nama Pasien',
                    'ruang' => 'Ruang',
                    'dokter' => 'Dokter Utama',
                    'sistemBayar' => 'Sistem Bayar',
                    'nota' => 'Nota',
                    'tglTrans' => 'Tgl Transaksi',
                    'namaTrans' => 'Nama Transaksi',
                    'jml' => 'Jml',
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    /*select v_rad.*,round(v_rad.pelayanan*masterJM.langsung*masterJM.medis) as medis,
                    		round(v_rad.pelayanan*masterJM.langsung*masterJM.profesiLain) as profesiLain,round(v_rad.pelayanan*masterJM.tdkLangsung*masterJM.posRemun) as posRemun,
                    		round(v_rad.pelayanan*masterJM.tdkLangsung*masterJM.manajemen) as manajemen from (*/
                    		SELECT rs47.rs1 AS kdTindakan,rs48.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs48.rs2 as nota,rs48.rs3 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs48.rs24 as jml,
                    		ROUND(rs48.rs6) AS sarana,ROUND(rs48.rs8) AS pelayanan,ROUND((rs48.rs6+rs48.rs8)) AS subtotal FROM rs48,rs9,rs23,rs15,v_gudang,rs21,rs47
                    		WHERE rs48.rs4=rs47.rs1 and rs23.rs19=rs9.rs1 AND rs48.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1
                    		AND rs48.rs26=v_gudang.rs1 AND rs23.rs10=rs21.rs1 AND rs23.rs22<>'' and rs48.rs26<>'POL014'
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
                    		union all
                    		SELECT rs47.rs1 AS kdTindakan,rs48.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs48.rs2 as nota,rs48.rs3 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs48.rs24 as jml,
                    		ROUND(rs48.rs6) AS sarana,ROUND(rs48.rs8) AS pelayanan,ROUND((rs48.rs6+rs48.rs8)) AS subtotal FROM rs17,rs48,rs9,rs15,v_gudang,rs21,rs47
                    		WHERE rs48.rs4=rs47.rs1 and rs17.rs14=rs9.rs1 AND rs48.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs48.rs26=v_gudang.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and rs48.rs26='POL014'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		union all
                    		SELECT rs47.rs1 AS kdTindakan,rs48.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs48.rs2 as nota,rs48.rs3 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs48.rs24 as jml,
                    		ROUND(rs48.rs6) AS sarana,ROUND(rs48.rs8) AS pelayanan,ROUND((rs48.rs6+rs48.rs8)) AS subtotal FROM rs48,rs9,rs17,rs15,v_gudang,rs21,rs47
                    		WHERE rs48.rs4=rs47.rs1 and rs17.rs14=rs9.rs1 AND rs48.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs48.rs26=v_gudang.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and rs48.rs26<>'POL014' and
                    		rs48.rs26<>'BG' and rs48.rs26<>'BR' and rs48.rs26<>'DA' and rs48.rs26<>'FA' and rs48.rs26<>'IC' and rs48.rs26<>'ICC' and rs48.rs26<>'MA' and rs48.rs26<>'ME'
                    		and rs48.rs26<>'WK' and rs48.rs26<>'WKUT' and rs48.rs26<>'WKVVIP' and rs48.rs26<>'KA' and rs48.rs26<>'WKKB' and rs48.rs26<>'ISHK'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		union all
                    		SELECT rs47.rs1 AS kdTindakan,'' AS noreg,'' AS tglMasuk,'' AS tglKeluar,'' AS norm,
                    		rs270.rs2 AS namaPasien,'Permintaan Luar' AS ruang,rs270.rs6 AS dokter,
                    		'' AS sistemBayar,rs270.rs1 as nota,rs270.rs8 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs271.rs10 as jml,
                    		ROUND(rs271.rs5) AS sarana,ROUND(rs271.rs7) AS pelayanan,
                    		ROUND((rs271.rs5+rs271.rs7)) AS subtotal FROM rs270,rs271,rs47
                    		WHERE rs270.rs1=rs271.rs1 and rs271.rs3=rs47.rs1
                    		AND DATE(rs270.rs8)>=? AND DATE(rs270.rs8)<=?
                    		/*) as v_rad left join masterJM on masterJM.idMaster='PEN003' and masterJM.tblMaster='rs19'*/
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            4 => [
                'title' => 'Rincian Operasi',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'norm' => 'No.RM',
                    'namaPasien' => 'Nama Pasien',
                    'ruang' => 'Ruang',
                    'dokter' => 'Dokter Utama',
                    'sistemBayar' => 'Sistem Bayar',
                    'nota' => 'Nota',
                    'tglTrans' => 'Tgl Transaksi',
                    'namaTrans' => 'Nama Transaksi',
                    'jml' => 'Jml',
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    /*select v_rad.*,round(v_rad.pelayanan*masterJM.langsung*masterJM.medis) as medis,
                    		round(v_rad.pelayanan*masterJM.langsung*masterJM.profesiLain) as profesiLain,round(v_rad.pelayanan*masterJM.tdkLangsung*masterJM.posRemun) as posRemun,
                    		round(v_rad.pelayanan*masterJM.tdkLangsung*masterJM.manajemen) as manajemen from (*/
                    		SELECT rs47.rs1 AS kdTindakan,rs48.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs48.rs2 as nota,rs48.rs3 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs48.rs24 as jml,
                    		ROUND(rs48.rs6) AS sarana,ROUND(rs48.rs8) AS pelayanan,ROUND((rs48.rs6+rs48.rs8)) AS subtotal FROM rs48,rs9,rs23,rs15,v_gudang,rs21,rs47
                    		WHERE rs48.rs4=rs47.rs1 and rs23.rs19=rs9.rs1 AND rs48.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1
                    		AND rs48.rs26=v_gudang.rs1 AND rs23.rs10=rs21.rs1 AND rs23.rs22<>'' and rs48.rs26<>'POL014'
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
                    		union all
                    		SELECT rs47.rs1 AS kdTindakan,rs48.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs48.rs2 as nota,rs48.rs3 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs48.rs24 as jml,
                    		ROUND(rs48.rs6) AS sarana,ROUND(rs48.rs8) AS pelayanan,ROUND((rs48.rs6+rs48.rs8)) AS subtotal FROM rs17,rs48,rs9,rs15,v_gudang,rs21,rs47
                    		WHERE rs48.rs4=rs47.rs1 and rs17.rs14=rs9.rs1 AND rs48.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs48.rs26=v_gudang.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and rs48.rs26='POL014'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		union all
                    		SELECT rs47.rs1 AS kdTindakan,rs48.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs48.rs2 as nota,rs48.rs3 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs48.rs24 as jml,
                    		ROUND(rs48.rs6) AS sarana,ROUND(rs48.rs8) AS pelayanan,ROUND((rs48.rs6+rs48.rs8)) AS subtotal FROM rs48,rs9,rs17,rs15,v_gudang,rs21,rs47
                    		WHERE rs48.rs4=rs47.rs1 and rs17.rs14=rs9.rs1 AND rs48.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs48.rs26=v_gudang.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and rs48.rs26<>'POL014' and
                    		rs48.rs26<>'BG' and rs48.rs26<>'BR' and rs48.rs26<>'DA' and rs48.rs26<>'FA' and rs48.rs26<>'IC' and rs48.rs26<>'ICC' and rs48.rs26<>'MA' and rs48.rs26<>'ME'
                    		and rs48.rs26<>'WK' and rs48.rs26<>'WKUT' and rs48.rs26<>'WKVVIP' and rs48.rs26<>'KA' and rs48.rs26<>'WKKB' and rs48.rs26<>'ISHK'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		union all
                    		SELECT rs47.rs1 AS kdTindakan,'' AS noreg,'' AS tglMasuk,'' AS tglKeluar,'' AS norm,
                    		rs270.rs2 AS namaPasien,'Permintaan Luar' AS ruang,rs270.rs6 AS dokter,
                    		'' AS sistemBayar,rs270.rs1 as nota,rs270.rs8 AS tglTrans,concat(rs47.rs2,' (',rs47.rs3,')') AS namaTrans,rs271.rs10 as jml,
                    		ROUND(rs271.rs5) AS sarana,ROUND(rs271.rs7) AS pelayanan,
                    		ROUND((rs271.rs5+rs271.rs7)) AS subtotal FROM rs270,rs271,rs47
                    		WHERE rs270.rs1=rs271.rs1 and rs271.rs3=rs47.rs1
                    		AND DATE(rs270.rs8)>=? AND DATE(rs270.rs8)<=?
                    		/*) as v_rad left join masterJM on masterJM.idMaster='PEN003' and masterJM.tblMaster='rs19'*/
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            5 => [
                'title' => 'Rincian Tindakan RR',
                'columns' => [
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
                ],
                'sql' => <<<'QUERY'
                    SELECT rs73.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,rs73.rs8 as kdPelaksanan1,rs73.rs23 as kdPelaksanan2,
                    		rs9.rs2 AS sistemBayar,rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,if(potongan_jasa.id_trans is not null,
                    		ROUND(rs73.rs13*rs73.rs5),0) as potjas,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,if(potongan_jasa.id_trans is not null,
                    		ROUND(((rs73.rs7+rs73.rs13)*rs73.rs5)-(rs73.rs13*rs73.rs5)),ROUND((rs73.rs7+rs73.rs13)*rs73.rs5)) AS subtotal FROM rs73
                    		LEFT JOIN potongan_jasa on rs73.id=potongan_jasa.id_trans
                    		and (potongan_jasa.jenis='tindakan_ibs' or potongan_jasa.jenis='tindakan_okigd'),rs23,rs15,rs24,rs21,rs30,rs9
                    		WHERE rs73.rs4=rs30.rs1 and rs23.rs19=rs9.rs1 AND rs73.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1
                    		AND rs23.rs5=rs24.rs1 AND (rs73.rs22='OPERASI' or rs73.rs22='OPERASIIRD') AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
                    		union all
                    		SELECT rs73.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,rs73.rs8 as kdPelaksanan1,rs73.rs23 as kdPelaksanan2,
                    		rs9.rs2 AS sistemBayar,rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,if(potongan_jasa.id_trans is not null,
                    		ROUND(rs73.rs13*rs73.rs5),0) as potjas,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,if(potongan_jasa.id_trans is not null,
                    		ROUND(((rs73.rs7+rs73.rs13)*rs73.rs5)-(rs73.rs13*rs73.rs5)),ROUND((rs73.rs7+rs73.rs13)*rs73.rs5)) AS subtotal FROM rs73
                    		LEFT JOIN potongan_jasa on rs73.id=potongan_jasa.id_trans
                    		and (potongan_jasa.jenis='tindakan_ibs' or potongan_jasa.jenis='tindakan_okigd'),rs17,rs15,rs19,rs21,rs30,rs9
                    		WHERE rs73.rs4=rs30.rs1 and rs17.rs14=rs9.rs1 AND rs73.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs17.rs8=rs19.rs1 AND (rs73.rs22='OPERASI2' or rs73.rs22='OPERASIIRD2') and rs17.rs8='POL014' AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		union all
                    		SELECT rs73.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,rs73.rs8 as kdPelaksanan1,rs73.rs23 as kdPelaksanan2,
                    		rs9.rs2 AS sistemBayar,rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,if(potongan_jasa.id_trans is not null,
                    		ROUND(rs73.rs13*rs73.rs5),0) as potjas,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,if(potongan_jasa.id_trans is not null,
                    		ROUND(((rs73.rs7+rs73.rs13)*rs73.rs5)-(rs73.rs13*rs73.rs5)),ROUND((rs73.rs7+rs73.rs13)*rs73.rs5)) AS subtotal FROM rs73
                    		LEFT JOIN potongan_jasa on rs73.id=potongan_jasa.id_trans
                    		and (potongan_jasa.jenis='tindakan_ibs' or potongan_jasa.jenis='tindakan_okigd'),rs17,rs15,rs19,rs21,rs30,rs9
                    		WHERE rs73.rs4=rs30.rs1 and rs17.rs14=rs9.rs1 AND rs73.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs17.rs8=rs19.rs1 AND rs73.rs22='OPERASI' and rs17.rs8<>'POL014' AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            6 => [
                'title' => 'Rincian Fisioterapi',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'norm' => 'No.RM',
                    'namaPasien' => 'Nama Pasien',
                    'ruang' => 'Ruang',
                    'dokter' => 'Dokter Utama',
                    'pelaksana' => 'Pelaksana',
                    'namaTrans' => 'Nama Transaksi',
                    'tglTrans' => 'Tgl Transaksi',
                    'jml' => 'Jumlah',
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'sistemBayar' => 'Sistem Bayar',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    SELECT rs73.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 as ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs30.rs2 AS namaTrans,rs73.rs3 as tglTrans,
                    		rs73.rs3 AS tglTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,rs9.rs2 AS sistemBayar,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73
                            left join rs17 on rs73.rs1=rs17.rs1
                            left join rs201 on rs73.rs1=rs201.rs1
                            left join rs21 on rs201.rs16=rs21.rs1
                            left join rs21 AS tpelaksana on rs73.rs8=tpelaksana.rs1
                            left join rs15 on rs17.rs2=rs15.rs1
                            left join rs30 on rs73.rs4=rs30.rs1
                            left join rs9 on rs17.rs14=rs9.rs1
                    		left join rs19 on rs17.rs8=rs19.rs1
                    		WHERE rs73.rs22='FISIO' AND rs17.rs19='1'
                    		AND rs17.rs3>=? ' 00:00:00' AND rs17.rs3<=? ' 23:59:59'
                    		union all
                    		SELECT rs73.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs23.rs24 as ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs30.rs2 AS namaTrans,rs73.rs3 as tglTrans,
                    		rs73.rs3 AS tglTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,rs9.rs2 AS sistemBayar,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal
                            FROM rs73
                            left join rs23 on rs73.rs1=rs23.rs1
                            left join rs30 on rs73.rs4=rs30.rs1
                            left join rs21 on rs23.rs10=rs21.rs1
                            left join rs21 AS tpelaksana on rs73.rs8=tpelaksana.rs1
                            left join rs15 on rs23.rs2=rs15.rs1
                            left join rs9 on rs23.rs19=rs9.rs1
                            left join rs19 on rs23.rs19=rs9.rs1
                    		WHERE rs73.rs22='FISIO' AND rs73.rs25<>'POL014' AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND rs23.rs4>=? ' 00:00:00' AND rs23.rs4<=? ' 23:59:59'
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            7 => [
                'title' => 'Rincian Hemodialisa',
                'columns' => [
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
                ],
                'sql' => <<<'QUERY'
                    SELECT rs73.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs17,rs15,rs19,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs17.rs14=rs9.rs1
                    		AND rs73.rs25=rs19.rs1 AND rs73.rs22='PEN005' AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		UNION ALL
                    		SELECT rs73.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs23,rs15,rs24,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs23.rs19=rs9.rs1
                    		AND rs23.rs5=rs24.rs1 AND rs73.rs22='PEN005' AND rs73.rs25<>'POL014' AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            8 => [
                'title' => 'Rincian Cardio',
                'columns' => [
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
                ],
                'sql' => <<<'QUERY'
                    SELECT rs73.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs17,rs15,rs19,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs17.rs14=rs9.rs1
                    		AND rs73.rs25=rs19.rs1 AND rs73.rs22='POL026' AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		UNION ALL
                    		SELECT rs73.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs23,rs15,rs24,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs23.rs19=rs9.rs1
                    		AND rs23.rs5=rs24.rs1 AND rs73.rs22='POL026' AND rs73.rs25<>'POL014' AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            9 => [
                'title' => 'Rincian EEG',
                'columns' => [
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
                ],
                'sql' => <<<'QUERY'
                    SELECT rs73.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs17,rs15,rs19,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs17.rs14=rs9.rs1
                    		AND rs73.rs25=rs19.rs1 AND rs73.rs22='POL024' AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		UNION ALL
                    		SELECT rs73.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs23,rs15,rs24,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs23.rs19=rs9.rs1
                    		AND rs23.rs5=rs24.rs1 AND rs73.rs22='POL024' AND rs73.rs25<>'POL014' AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            10 => [
                'title' => 'Rincian Endoscope',
                'columns' => [
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
                ],
                'sql' => <<<'QUERY'
                    SELECT rs73.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs17,rs15,rs19,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs17.rs14=rs9.rs1
                    		AND rs73.rs16=rs19.rs1 AND rs73.rs22='POL031' AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		UNION ALL
                    		SELECT rs73.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		rs73.rs3 AS tglTrans,rs30.rs2 AS namaTrans,rs73.rs5 AS jml,ROUND(rs73.rs7) AS sarana,
                    		ROUND(rs73.rs13) AS pelayanan,ROUND((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal FROM rs73,rs9,rs23,rs15,rs24,rs21,rs21 AS tpelaksana,rs30
                    		WHERE rs73.rs4=rs30.rs1 AND rs73.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1 AND rs73.rs8=tpelaksana.rs1 and rs23.rs19=rs9.rs1
                    		AND rs73.rs16=rs24.rs1 AND rs73.rs22='POL031' AND rs73.rs25<>'POL014' AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            11 => [
                'title' => 'Rincian Psikologi',
                'columns' => [
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
                ],
                'sql' => <<<'QUERY'
                    SELECT psikologi_trans.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		psikologi_trans.rs3 AS tglTrans,rs30.rs2 AS namaTrans,psikologi_trans.rs5 AS jml,ROUND(psikologi_trans.rs7) AS sarana,
                    		ROUND(psikologi_trans.rs13) AS pelayanan,ROUND((psikologi_trans.rs7+psikologi_trans.rs13)*psikologi_trans.rs5) AS subtotal FROM psikologi_trans,rs9,rs17,rs15,rs19,rs21,rs21 AS tpelaksana,rs30
                    		WHERE psikologi_trans.rs4=rs30.rs1 AND psikologi_trans.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1 AND psikologi_trans.rs8=tpelaksana.rs1 and rs17.rs14=rs9.rs1
                    		AND psikologi_trans.rs16=rs19.rs1 AND psikologi_trans.rs22='POL037' AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		UNION ALL
                    		SELECT psikologi_trans.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,tpelaksana.rs2 AS pelaksana,rs9.rs2 AS sistemBayar,
                    		psikologi_trans.rs3 AS tglTrans,rs30.rs2 AS namaTrans,psikologi_trans.rs5 AS jml,ROUND(psikologi_trans.rs7) AS sarana,
                    		ROUND(psikologi_trans.rs13) AS pelayanan,ROUND((psikologi_trans.rs7+psikologi_trans.rs13)*psikologi_trans.rs5) AS subtotal FROM psikologi_trans,rs9,rs23,rs15,rs24,rs21,rs21 AS tpelaksana,rs30
                    		WHERE psikologi_trans.rs4=rs30.rs1 AND psikologi_trans.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1 AND psikologi_trans.rs8=tpelaksana.rs1 and rs23.rs19=rs9.rs1
                    		AND psikologi_trans.rs16=rs24.rs1 AND psikologi_trans.rs22='POL037' AND psikologi_trans.rs25<>'POL014' AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            12 => [
                'title' => 'Rincian Gizi',
                'columns' => [
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
                ],
                'sql' => <<<'QUERY'
                    SELECT rs202.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs202.rs2 AS tglTrans,rs30tarif.rs2 AS namaTrans,
                    		ROUND(rs202.rs4) AS sarana,ROUND(rs202.rs5) AS pelayanan,ROUND(rs202.rs4+rs202.rs5) AS subtotal
                    		FROM rs202,rs9,rs23,rs15,v_gudang,rs21,rs30tarif
                    		WHERE rs202.rs3=rs30tarif.rs1 and rs23.rs19=rs9.rs1 AND rs202.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1
                    		AND rs202.rs8=v_gudang.rs1 AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                ],
            ],
            13 => [
                'title' => 'Rincian Penggunaan Darah',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'norm' => 'No.RM',
                    'namaPasien' => 'Nama Pasien',
                    'ruang' => 'Ruang',
                    'dokter' => 'Dokter Utama',
                    'sistemBayar' => 'Sistem Bayar',
                    'tglTrans' => 'Tgl Transaksi',
                    'noKantong' => 'No.Kantong',
                    'jenis' => 'Jenis',
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    /*select v_darah.*,round(v_darah.pelayanan*0.6*0.3) as medis,round(v_darah.pelayanan*0.6*0.7) as profesiLain,
                    		round(v_darah.pelayanan*0.4*0.7) as posRemun,round(v_darah.pelayanan*0.4*0.3) as manajemen from (*/
                    		SELECT rs231.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs231.rs4 AS tglTrans,rs231.rs5 AS noKantong,rs231.rs6 AS jenis,
                    		ROUND(rs231.rs12) AS sarana,ROUND(rs231.rs13) AS pelayanan,ROUND(rs231.rs12+rs231.rs13) AS subtotal
                    		FROM rs231,rs9,rs23,rs15,v_gudang,rs21 where
                    		rs23.rs19=rs9.rs1 AND rs231.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1
                    		AND rs231.rs14=v_gudang.rs1 AND rs23.rs10=rs21.rs1 AND rs23.rs22<>'' and rs231.rs14<>'POL014'
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
                    		UNION ALL
                    		SELECT rs231.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs231.rs4 AS tglTrans,rs231.rs5 AS noKantong,rs231.rs6 AS jenis,
                    		ROUND(rs231.rs12) AS sarana,ROUND(rs231.rs13) AS pelayanan,ROUND(rs231.rs12+rs231.rs13) AS subtotal
                    		FROM rs231,rs9,rs17,rs15,v_gudang,rs21 where
                    		rs17.rs14=rs9.rs1 AND rs231.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs231.rs14=v_gudang.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and rs231.rs14='POL014'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
                    		UNION ALL
                    		SELECT rs231.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,v_gudang.rs2 AS ruang,rs21.rs2 AS dokter,
                    		rs9.rs2 AS sistemBayar,rs231.rs4 AS tglTrans,rs231.rs5 AS noKantong,rs231.rs6 AS jenis,
                    		ROUND(rs231.rs12) AS sarana,ROUND(rs231.rs13) AS pelayanan,ROUND(rs231.rs12+rs231.rs13) AS subtotal
                    		FROM rs231,rs9,rs17,rs15,v_gudang,rs21 where
                    		rs17.rs14=rs9.rs1 AND rs231.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs231.rs14=v_gudang.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and rs17.rs8<>'POL014'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=? /*) as v_darah*/
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            14 => [
                'title' => 'Rincian Farmasi',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'norm' => 'No.RM',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'namaPasien' => 'Nama Pasien',
                    'depo' => 'Depo',
                    'dokter' => 'Dokter Utama',
                    'nota' => 'Nota',
                    'embalage' => 'Embalage',
                    'subtotal' => 'Subtotal',
                    'total' => 'Total',
                ],
                'sql' => <<<'QUERY'
                    select * from (
                    				select rs.rs23.rs1 as noreg,rs.rs23.rs2 as norm,rs.rs23.rs3 as tglMasuk,rs.rs23.rs4 as tglKeluar,rs.rs15.rs2 as namaPasien,
                    				rs.rs9.rs2 as sitembayar,
                    				rs.rs86.rs2 as depo,kepegx.pegawai.nama as dokter,farmasi.resep_keluar_h.noresep as nota,
                    				sum(farmasi.resep_keluar_r.nilai_r) as embalage,sum(farmasi.resep_keluar_r.harga_jual*farmasi.resep_keluar_r.jumlah) as subtotal,
                    				sum((farmasi.resep_keluar_r.harga_jual*farmasi.resep_keluar_r.jumlah)+farmasi.resep_keluar_r.nilai_r) as total
                    				from rs.rs23
                    				left join farmasi.resep_keluar_h on farmasi.resep_keluar_h.noreg =rs.rs23.rs1
                    				left join farmasi.resep_keluar_r on farmasi.resep_keluar_r.noresep=farmasi.resep_keluar_h.noresep
                    				left join rs.rs15 on rs.rs23.rs2=rs.rs15.rs1
                    				left join rs.rs86 on rs.rs86.rs1=farmasi.resep_keluar_h.depo
                    				left join kepegx.pegawai on kepegx.pegawai.kdpegsimrs=farmasi.resep_keluar_h.dokter
                    				left join rs.rs9 on rs.rs9.rs1 =farmasi.resep_keluar_h.sistembayar
                    				where rs.rs23.rs4 BETWEEN ? AND ?
                    				and farmasi.resep_keluar_h.noreg<>'' and farmasi.resep_keluar_r.noreg<>'' and farmasi.resep_keluar_h.ruangan<>'POL014'
                    			   group by farmasi.resep_keluar_h.noresep
                    			   union all
                    			   select rs.rs23.rs1 as noreg,rs.rs23.rs2 as norm,rs.rs23.rs3 as tglMasuk,rs.rs23.rs4 as tglKeluar,rs.rs15.rs2 as namapasien,
                    			   rs.rs9.rs2 as sitembayar,
                    			   rs.rs86.rs2 as depo,kepegx.pegawai.nama as dokter,farmasi.resep_keluar_h.noresep as nota,
                    			   sum(farmasi.resep_keluar_racikan_r.nilai_r) as embalage,sum((farmasi.resep_keluar_racikan_r.jumlah*farmasi.resep_keluar_racikan_r.harga_jual)) as subtotal,
                    				sum((farmasi.resep_keluar_racikan_r.jumlah*farmasi.resep_keluar_racikan_r.harga_jual)+farmasi.resep_keluar_racikan_r.nilai_r) as total
                    				from rs.rs23
                    				left join farmasi.resep_keluar_h on farmasi.resep_keluar_h.noreg =rs.rs23.rs1
                    				left join farmasi.resep_keluar_racikan_r on farmasi.resep_keluar_racikan_r.noresep=farmasi.resep_keluar_h.noresep
                    				left join farmasi.new_masterobat on farmasi.new_masterobat.kd_obat=farmasi.resep_keluar_racikan_r.kdobat
                    				left join rs.rs15 on rs.rs23.rs2=rs.rs15.rs1
                    				left join rs.rs86 on rs.rs86.rs1=farmasi.resep_keluar_h.depo
                    				left join rs.rs9 on rs.rs9.rs1 =farmasi.resep_keluar_h.sistembayar
                    				left join kepegx.pegawai on kepegx.pegawai.kdpegsimrs=farmasi.resep_keluar_h.dokter
                    				where rs.rs23.rs4 BETWEEN ? AND ?
                    				and farmasi.resep_keluar_h.noreg<>'' and farmasi.resep_keluar_racikan_r.noreg<>'' and farmasi.resep_keluar_h.ruangan<>'POL014'
                    				group by farmasi.resep_keluar_h.noresep,farmasi.resep_keluar_racikan_r.namaracikan
                    				union all
                    				select rs.rs17.rs1 as noreg,rs.rs17.rs2 as norm,rs.rs17.rs3 as tglMasuk,rs.rs17.rs3 as tglKeluar,rs.rs15.rs2 as namaPasien,
                    				rs.rs9.rs2 as sitembayar,
                    				rs.rs86.rs2 as depo,kepegx.pegawai.nama as dokter,farmasi.resep_keluar_h.noresep as nota,
                    				sum(farmasi.resep_keluar_r.nilai_r) as embalage,sum(farmasi.resep_keluar_r.harga_jual*farmasi.resep_keluar_r.jumlah) as subtotal,
                    				sum((farmasi.resep_keluar_r.harga_jual*farmasi.resep_keluar_r.jumlah)+farmasi.resep_keluar_r.nilai_r) as total
                    				from rs.rs17
                    				left join farmasi.resep_keluar_h on farmasi.resep_keluar_h.noreg =rs.rs17.rs1
                    				left join farmasi.resep_keluar_r on farmasi.resep_keluar_r.noresep=farmasi.resep_keluar_h.noresep
                    				left join rs.rs15 on rs.rs17.rs2=rs.rs15.rs1
                    				left join rs.rs86 on rs.rs86.rs1=farmasi.resep_keluar_h.depo
                    				left join rs.rs9 on rs.rs9.rs1 =farmasi.resep_keluar_h.sistembayar
                    				left join kepegx.pegawai on kepegx.pegawai.kdpegsimrs=farmasi.resep_keluar_h.dokter
                    				where rs.rs17.rs3 BETWEEN ? AND ?
                    				and farmasi.resep_keluar_h.noreg<>'' and farmasi.resep_keluar_r.noreg<>'' and farmasi.resep_keluar_h.ruangan='POL014'
                    				group by farmasi.resep_keluar_h.noresep
                    				union all
                    				select rs.rs17.rs1 as noreg,rs.rs17.rs2 as norm,rs.rs17.rs3 as tglMasuk,rs.rs17.rs3 as tglKeluar,rs.rs15.rs2 as namaPasien,
                    				rs.rs9.rs2 as sitembayar,
                    				rs.rs86.rs2 as depo,kepegx.pegawai.nama as dokter,farmasi.resep_keluar_h.noresep as nota,
                    				sum(farmasi.resep_keluar_racikan_r.nilai_r) as embalage,sum((farmasi.resep_keluar_racikan_r.jumlah*farmasi.resep_keluar_racikan_r.harga_jual)) as subtotal,
                    				sum((farmasi.resep_keluar_racikan_r.jumlah*farmasi.resep_keluar_racikan_r.harga_jual)+farmasi.resep_keluar_racikan_r.nilai_r) as total
                    				from rs.rs17
                    				left join farmasi.resep_keluar_h on farmasi.resep_keluar_h.noreg =rs.rs17.rs1
                    				left join farmasi.resep_keluar_racikan_r on farmasi.resep_keluar_racikan_r.noresep=farmasi.resep_keluar_h.noresep
                    				left join rs.rs15 on rs.rs17.rs2=rs.rs15.rs1
                    				left join rs.rs86 on rs.rs86.rs1=farmasi.resep_keluar_h.depo
                    				left join kepegx.pegawai on kepegx.pegawai.kdpegsimrs=farmasi.resep_keluar_h.dokter
                    				left join rs.rs9 on rs.rs9.rs1 =farmasi.resep_keluar_h.sistembayar
                    				where rs.rs17.rs3 BETWEEN ? AND ?
                    				and farmasi.resep_keluar_h.noreg<>'' and farmasi.resep_keluar_racikan_r.noreg<>'' and farmasi.resep_keluar_h.ruangan='POL014'
                    				group by farmasi.resep_keluar_h.noresep,farmasi.resep_keluar_racikan_r.namaracikan
                    				union all
                    				select rs.rs17.rs1 as noreg,rs.rs17.rs2 as norm,rs.rs17.rs3 as tglMasuk,rs.rs17.rs3 as tglKeluar,rs.rs15.rs2 as namaPasien,
                    				rs.rs9.rs2 as sitembayar,
                    				rs.rs86.rs2 as depo,kepegx.pegawai.nama as dokter,farmasi.resep_keluar_h.noresep as nota,
                    				sum(farmasi.resep_keluar_r.nilai_r) as embalage,sum(farmasi.resep_keluar_r.harga_jual*farmasi.resep_keluar_r.jumlah) as subtotal,
                    				sum((farmasi.resep_keluar_r.harga_jual*farmasi.resep_keluar_r.jumlah)+farmasi.resep_keluar_r.nilai_r) as total
                    				from rs.rs17
                    				left join farmasi.resep_keluar_h on farmasi.resep_keluar_h.noreg =rs.rs17.rs1
                    				left join farmasi.resep_keluar_r on farmasi.resep_keluar_r.noresep=farmasi.resep_keluar_h.noresep
                    				left join rs.rs15 on rs.rs17.rs2=rs.rs15.rs1
                    				left join rs.rs86 on rs.rs86.rs1=farmasi.resep_keluar_h.depo
                    				left join rs.rs9 on rs.rs9.rs1 =farmasi.resep_keluar_h.sistembayar
                    				left join kepegx.pegawai on kepegx.pegawai.kdpegsimrs=farmasi.resep_keluar_h.dokter
                    				where rs.rs17.rs3 BETWEEN ? AND ?
                    				and farmasi.resep_keluar_h.noreg<>'' and farmasi.resep_keluar_r.noreg<>'' and farmasi.resep_keluar_h.ruangan<>'POL014'
                    				group by farmasi.resep_keluar_h.noresep
                    				union ALL
                    				select rs.rs17.rs1 as noreg,rs.rs17.rs2 as norm,rs.rs17.rs3 as tglMasuk,rs.rs17.rs3 as tglKeluar,rs.rs15.rs2 as namaPasien,
                    				rs.rs9.rs2 as sitembayar,
                    				rs.rs86.rs2 as depo,kepegx.pegawai.nama as dokter,farmasi.resep_keluar_h.noresep as nota,
                    				sum(farmasi.resep_keluar_racikan_r.nilai_r) as embalage,sum((farmasi.resep_keluar_racikan_r.jumlah*farmasi.resep_keluar_racikan_r.harga_jual)) as subtotal,
                    				sum((farmasi.resep_keluar_racikan_r.jumlah*farmasi.resep_keluar_racikan_r.harga_jual)+farmasi.resep_keluar_racikan_r.nilai_r) as total
                    				from rs.rs17
                    				left join farmasi.resep_keluar_h on farmasi.resep_keluar_h.noreg =rs.rs17.rs1
                    				left join farmasi.resep_keluar_racikan_r on farmasi.resep_keluar_racikan_r.noresep=farmasi.resep_keluar_h.noresep
                    				left join rs.rs15 on rs.rs17.rs2=rs.rs15.rs1
                    				left join rs.rs86 on rs.rs86.rs1=farmasi.resep_keluar_h.depo
                    				left join kepegx.pegawai on kepegx.pegawai.kdpegsimrs=farmasi.resep_keluar_h.dokter
                    				left join rs.rs9 on rs.rs9.rs1 =farmasi.resep_keluar_h.sistembayar
                    				where rs.rs17.rs3 BETWEEN ? AND ?
                    				and farmasi.resep_keluar_h.noreg<>'' and farmasi.resep_keluar_racikan_r.noreg<>'' and farmasi.resep_keluar_h.ruangan<>'POL014'
                    				group by farmasi.resep_keluar_h.noresep,farmasi.resep_keluar_racikan_r.namaracikan
                    		) as wew order by tglKeluar
QUERY,
                'bindings' => [
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                    $fromStart,
                    $toEnd,
                ],
            ],
            15 => [
                'title' => 'Rincian Jenazah',
                'columns' => [
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
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    SELECT rs275.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,'' as pelaksana,
                    		rs9.rs2 AS sistemBayar,rs275.rs2 AS tglTrans,rs275.rs3 AS namaTrans,
                    		ROUND(rs275.rs5) AS sarana,ROUND(rs275.rs6) AS pelayanan,ROUND(rs275.rs5+rs275.rs6) AS subtotal
                    		FROM rs275,rs9,rs23,rs15,rs24,rs21
                    		WHERE rs23.rs19=rs9.rs1 AND rs275.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1
                    		AND rs23.rs5=rs24.rs1 AND rs23.rs10=rs21.rs1 AND rs23.rs22<>''
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=? and rs275.rs7<>'POL014'
                    		union all
                    		SELECT rs273.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,pelaksana.rs2 as pelaksana,
                    		rs9.rs2 AS sistemBayar,rs273.rs4 AS tglTrans,rs30.rs2 AS namaTrans,
                    		ROUND(rs273.rs6) AS sarana,ROUND(rs273.rs7) AS pelayanan,ROUND(rs273.rs6+rs273.rs7) AS subtotal
                    		FROM rs273,rs9,rs23,rs15,rs24,rs21,rs30,rs21 as pelaksana
                    		WHERE rs23.rs19=rs9.rs1 AND rs273.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1 and rs273.rs5=rs30.rs1
                    		AND rs23.rs5=rs24.rs1 AND rs23.rs10=rs21.rs1 AND rs23.rs22<>'' and pelaksana.rs1=rs273.rs9
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=? and rs273.rs14<>'POL014'
                    		union all
                    		SELECT rs275.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs5 AS ruang,rs21.rs2 AS dokter,'' as pelaksana,
                    		rs9.rs2 AS sistemBayar,rs275.rs2 AS tglTrans,rs275.rs3 AS namaTrans,
                    		ROUND(rs275.rs5) AS sarana,ROUND(rs275.rs6) AS pelayanan,ROUND(rs275.rs5+rs275.rs6) AS subtotal
                    		FROM rs275,rs9,rs17,rs15,rs19,rs21
                    		WHERE rs17.rs14=rs9.rs1 AND rs275.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1
                    		AND rs17.rs8=rs19.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=? and rs275.rs7='POL014'
                    		union all
                    		SELECT rs273.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,concat('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs5 AS ruang,rs21.rs2 AS dokter,pelaksana.rs2 as pelaksana,
                    		rs9.rs2 AS sistemBayar,rs273.rs4 AS tglTrans,rs30.rs2 AS namaTrans,
                    		ROUND(rs273.rs6) AS sarana,ROUND(rs273.rs7) AS pelayanan,ROUND(rs273.rs6+rs273.rs7) AS subtotal
                    		FROM rs273,rs9,rs17,rs15,rs19,rs21,rs30,rs21 as pelaksana
                    		WHERE rs17.rs19=rs9.rs1 AND rs273.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1 and rs273.rs5=rs30.rs1
                    		AND rs17.rs8=rs19.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and pelaksana.rs1=rs273.rs9
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=? and rs273.rs14='POL014'
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
            16 => [
                'title' => 'Rincian Ambulan',
                'columns' => [
                    'noreg' => 'No.Reg',
                    'tglMasuk' => 'Tgl Masuk',
                    'tglKeluar' => 'Tgl Keluar',
                    'norm' => 'No.RM',
                    'namaPasien' => 'Nama Pasien',
                    'ruang' => 'Ruang',
                    'dokter' => 'Dokter Utama',
                    'sopir' => 'Sopir',
                    'crew' => 'Crew',
                    'perawat1' => 'Perawat 1',
                    'perawat2' => 'Perawat 2',
                    'sistemBayar' => 'Sistem Bayar',
                    'tglTrans' => 'Tgl Transaksi',
                    'tujuan' => 'Tujuan',
                    'sarana' => 'Sarana',
                    'pelayanan' => 'Pelayanan',
                    'subtotal' => 'Subtotal',
                ],
                'sql' => <<<'QUERY'
                    SELECT rs283.rs1 AS noreg,rs23.rs3 AS tglMasuk,rs23.rs4 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs24.rs5 AS ruang,rs21.rs2 AS dokter,rs282.rs2 AS sopir,crew.rs2 AS crew,perawat.rs2 AS perawat1,perawat2.rs2 AS perawat2,
                    		rs9.rs2 AS sistemBayar,rs283.rs4 AS tglTrans,rs281.rs2 AS tujuan,
                    		ROUND(rs283.rs17) AS sarana,ROUND(rs283.rs18) AS pelayanan,ROUND(rs283.rs17+rs283.rs18) AS subtotal
                    		FROM rs283,rs9,rs23,rs15,rs24,rs21,rs281,rs282,rs282 AS crew,rs21 AS perawat,rs21 AS perawat2
                    		WHERE rs283.rs5=rs281.rs1 AND rs282.rs1=rs283.rs10 AND rs23.rs19=rs9.rs1 AND rs283.rs1=rs23.rs1 AND rs23.rs2=rs15.rs1 AND rs283.rs20=rs24.rs1
                    		AND crew.rs1=rs283.rs11 AND perawat.rs1=rs283.rs13 AND perawat2.rs1=rs283.rs14 AND rs23.rs5=rs24.rs1 AND rs23.rs10=rs21.rs1 AND rs23.rs22<>'' and rs283.rs20<>'POL014'
                    		AND DATE(rs23.rs4)>=? AND DATE(rs23.rs4)<=?
                    		UNION ALL
                    		SELECT rs283.rs1 AS noreg,rs17.rs3 AS tglMasuk,rs17.rs3 AS tglKeluar,CONCAT('&nbsp;',rs15.rs1) AS norm,
                    		rs15.rs2 AS namaPasien,rs19.rs2 AS ruang,rs21.rs2 AS dokter,rs282.rs2 AS sopir,crew.rs2 AS crew,perawat.rs2 AS perawat1,perawat2.rs2 AS perawat2,
                    		rs9.rs2 AS sistemBayar,rs283.rs4 AS tglTrans,rs281.rs2 AS tujuan,
                    		ROUND(rs283.rs17) AS sarana,ROUND(rs283.rs18) AS pelayanan,ROUND(rs283.rs17+rs283.rs18) AS subtotal
                    		FROM rs283,rs9,rs17,rs15,rs19,rs21,rs281,rs282,rs282 AS crew,rs21 AS perawat,rs21 AS perawat2
                    		WHERE rs283.rs5=rs281.rs1 AND rs282.rs1=rs283.rs10 AND rs17.rs14=rs9.rs1 AND rs283.rs1=rs17.rs1 AND rs17.rs2=rs15.rs1 AND rs283.rs20=rs19.rs1
                    		AND crew.rs1=rs283.rs11 AND perawat.rs1=rs283.rs13 AND perawat2.rs1=rs283.rs14 AND rs17.rs8=rs19.rs1 AND rs17.rs9=rs21.rs1 AND rs17.rs19='1' and rs283.rs20='POL014'
                    		AND DATE(rs17.rs3)>=? AND DATE(rs17.rs3)<=?
QUERY,
                'bindings' => [
                    $from,
                    $to,
                    $from,
                    $to,
                ],
            ],
        ];

        abort_unless(isset($reports[$jenisLaporan]), 422, 'Jenis laporan Penunjang belum tersedia.');

        $report = $reports[$jenisLaporan];
        $rows = DB::select($report['sql'], $report['bindings']);

        return [
            'Title' => $report['title'],
            'Columns' => $report['columns'],
            'Total' => count($rows),
            'sRow' => $rows,
        ];
    }
}

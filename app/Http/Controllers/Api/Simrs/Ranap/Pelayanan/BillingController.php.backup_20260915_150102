<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
/**
     * Get Rekap Billing Rawat Inap
     * Mengadopsi 100% logika billing.php legacy
     */
        /**
     * Get Rekap Billing Rawat Inap (Unified: Global + Per Ruangan Singgah dalam 1 Response)
     */
    public function getRekapBilling(Request $request)
    {
        $noreg = trim($request->noreg);
        if (!$noreg) {
            return new JsonResponse(['message' => 'Noreg tidak boleh kosong'], 422);
        }

        $pasien = DB::table('rs23')
            ->join('rs15', 'rs15.rs1', '=', 'rs23.rs2')
            ->leftJoin('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->leftJoin('rs9', 'rs9.rs1', '=', 'rs23.rs19')
            ->leftJoin('rs21', 'rs21.rs1', '=', 'rs23.rs10')
            ->where('rs23.rs1', $noreg)
            ->select(
                'rs23.rs1 as noreg',
                'rs23.rs2 as norm',
                'rs15.rs2 as nama',
                'rs15.rs4 as alamat',
                'rs15.rs17 as kelamin',
                'rs15.rs16 as tgllahir',
                'rs15.rs33 as pekerjaan',
                'rs23.rs3 as tglmasuk',
                'rs23.rs4 as tglkeluar',
                'rs23.rs5 as koderuangankamar',
                'rs24.rs2 as ruang_poli',
                'rs24.rs3 as kelas',
                'rs24.rs4 as kd_ruangpoli',
                'rs23.rs19 as kodesistembayar',
                'rs9.rs2 as sistembayar',
                'rs23.rs10 as kodedokter',
                'rs21.rs2 as dokter'
            )
            ->first();

        if (!$pasien) {
            return new JsonResponse(['message' => 'Data pasien ranap tidak ditemukan'], 404);
        }

        $umurStr = '-';
        if ($pasien->tgllahir && $pasien->tgllahir !== '1900-01-01') {
            $tglLahirObj = new \DateTime($pasien->tgllahir);
            $tglMasukObj = new \DateTime($pasien->tglmasuk);
            $diff = $tglLahirObj->diff($tglMasukObj);
            if ($diff->y > 0) {
                $umurStr = $diff->y . ' thn';
            } elseif ($diff->m > 0) {
                $umurStr = $diff->m . ' bln';
            } else {
                $umurStr = $diff->d . ' hari';
            }
        }

        $tglmasukIgd = null;
        $cekIgd = DB::table('rs17')->where('rs1', $noreg)->select('rs3')->first();
        if ($cekIgd) {
            $tglmasukIgd = $cekIgd->rs3;
        }

        $tarifKamarPerHari = 0;
        $tarifKamarRow = DB::table('rs23')
            ->join('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->join('rs30tarif', function ($join) {
                $join->on('rs30tarif.rs4', 'like', DB::raw("CONCAT('%', rs24.rs4, '%')"))
                    ->where('rs30tarif.rs3', '=', 'K1#');
            })
            ->where('rs23.rs1', $noreg)
            ->select('rs24.rs3 as kelas', 'rs30tarif.rs6', 'rs30tarif.rs7', 'rs30tarif.rs8', 'rs30tarif.rs9', 'rs30tarif.rs10', 'rs30tarif.rs11', 'rs30tarif.rs12', 'rs30tarif.rs13', 'rs30tarif.rs14', 'rs30tarif.rs15', 'rs30tarif.rs16', 'rs30tarif.rs17')
            ->first();

        if ($tarifKamarRow) {
            $sarana = 0;
            $pelayanan = 0;
            switch ($tarifKamarRow->kelas) {
                case '3':
                case 'IC':
                case 'ICC':
                case 'NICU':
                case 'IN':
                    $sarana = (float)$tarifKamarRow->rs6;
                    $pelayanan = (float)$tarifKamarRow->rs7;
                    break;
                case '2':
                    $sarana = (float)$tarifKamarRow->rs8;
                    $pelayanan = (float)$tarifKamarRow->rs9;
                    break;
                case '1':
                case 'HCU':
                    $sarana = (float)$tarifKamarRow->rs10;
                    $pelayanan = (float)$tarifKamarRow->rs11;
                    break;
                case 'Utama':
                    $sarana = (float)$tarifKamarRow->rs12;
                    $pelayanan = (float)$tarifKamarRow->rs13;
                    break;
                case 'VIP':
                    $sarana = (float)$tarifKamarRow->rs14;
                    $pelayanan = (float)$tarifKamarRow->rs15;
                    break;
                case 'VVIP':
                    $sarana = (float)$tarifKamarRow->rs16;
                    $pelayanan = (float)$tarifKamarRow->rs17;
                    break;
            }
            $tarifKamarPerHari = $sarana + $pelayanan;
        }

        // 1. Ambil data ruangan dan sistem bayar dari rs35x (transaksi kamar K1#)
        $stays = DB::table('rs35x')
            ->leftJoin('rs24', 'rs24.rs1', '=', 'rs35x.rs18')
            ->leftJoin('rs9', 'rs9.rs1', '=', 'rs35x.rs8')
            ->where('rs35x.rs1', $noreg)
            ->where('rs35x.rs3', 'k1#')
            ->whereNotNull('rs35x.rs16')
            ->where('rs35x.rs16', '!=', '')
            ->select(
                'rs35x.rs16 as kd_ruangan',
                DB::raw("COALESCE(rs24.rs5, rs24.rs2, rs35x.rs16) as nama_ruangan"),
                'rs35x.rs18 as kdkamar',
                'rs35x.rs8 as kd_sistembayar',
                'rs9.rs2 as nama_sistembayar'
            )
            ->orderBy('rs35x.rs4', 'asc')
            ->get();

        // Fallback jika rs35x kosong (misal pasien baru masuk hari ini)
        if ($stays->isEmpty() && $pasien->kd_ruangpoli) {
            $stays = collect([[
                'kd_ruangan' => $pasien->kd_ruangpoli,
                'nama_ruangan' => $pasien->ruang_poli,
                'kdkamar' => $pasien->koderuangankamar,
                'kd_sistembayar' => $pasien->kodesistembayar,
                'nama_sistembayar' => $pasien->sistembayar
            ]]);
        }

        // List Ruangan (HANYA ruangan singgah + Semua Ruangan)
        $listRuangan = [
            [
                'kd_ruangan' => 'ALL',
                'nama_ruangan' => 'Semua Ruangan'
            ]
        ];
        $ruanganUnique = $stays->unique('kd_ruangan')->values();
        foreach ($ruanganUnique as $ru) {
            $rObj = (object)$ru;
            $listRuangan[] = [
                'kd_ruangan' => $rObj->kd_ruangan,
                'nama_ruangan' => $rObj->nama_ruangan
            ];
        }

        // List Sistem Bayar (HANYA sistem bayar yang dipakai pasien + Semua Sistem Bayar)
        $listSistemBayar = [
            [
                'kd_sistembayar' => 'ALL',
                'nama_sistembayar' => 'Semua Sistem Bayar'
            ]
        ];
        $sbUnique = $stays->whereNotNull('kd_sistembayar')->where('kd_sistembayar', '!=', '')->unique('kd_sistembayar')->values();
        if ($sbUnique->isEmpty() && $pasien->kodesistembayar) {
            $sbUnique = collect([[
                'kd_sistembayar' => $pasien->kodesistembayar,
                'nama_sistembayar' => $pasien->sistembayar
            ]]);
        }
        $existingSbKodes = $sbUnique->pluck('kd_sistembayar')->toArray();
        if ($pasien->kodesistembayar && !in_array($pasien->kodesistembayar, $existingSbKodes)) {
            $sbUnique->push((object)[
                'kd_sistembayar' => $pasien->kodesistembayar,
                'nama_sistembayar' => $pasien->sistembayar
            ]);
        }
        $namaSbMap = [];
        foreach ($sbUnique as $sbu) {
            $sObj = (object)$sbu;
            $namaSbMap[$sObj->kd_sistembayar] = $sObj->nama_sistembayar;
            $listSistemBayar[] = [
                'kd_sistembayar' => $sObj->kd_sistembayar,
                'nama_sistembayar' => $sObj->nama_sistembayar
            ];
        }

        // 2. Hitung Data Rekap GLOBAL (billing.php lengkap)
        $dataGlobal = $this->calculateBillingGlobal($noreg, $pasien, $tarifKamarPerHari, $umurStr, $tglmasukIgd);

        // 3. Hitung Data Rekap Kombinasi Details (Ruangan x Sistem Bayar)
        $details = [];
        $details['ALL__ALL'] = $dataGlobal;

        // Global per Sistem Bayar (billingbysistembayar.php)
        foreach ($sbUnique as $sbu) {
            $sObj = (object)$sbu;
            $details['ALL__' . $sObj->kd_sistembayar] = $this->calculateBillingBySistemBayar(
                $noreg,
                $pasien,
                $sObj->kd_sistembayar,
                $sObj->nama_sistembayar,
                $tarifKamarPerHari,
                $umurStr,
                $tglmasukIgd
            );
        }

        // Per Ruangan Singgah
        $perRuangan = [];
        foreach ($ruanganUnique as $ru) {
            $rObj = (object)$ru;
            $kdR = $rObj->kd_ruangan;
            $namaR = $rObj->nama_ruangan;
            $kdKamar = $rObj->kdkamar ?? '';

            // Ruangan dengan Semua Sistem Bayar
            $roomAllSb = $this->calculateBillingPerRuangan(
                $noreg,
                $pasien,
                $kdR,
                'ALL',
                $kdKamar,
                $namaR,
                $tarifKamarPerHari,
                $umurStr,
                $pasien->sistembayar
            );
            $details[$kdR . '__ALL'] = $roomAllSb;
            $perRuangan[$kdR] = $roomAllSb;

            // Ruangan dengan masing-masing Sistem Bayar
            foreach ($sbUnique as $sbu) {
                $sObj = (object)$sbu;
                $details[$kdR . '__' . $sObj->kd_sistembayar] = $this->calculateBillingPerRuangan(
                    $noreg,
                    $pasien,
                    $kdR,
                    $sObj->kd_sistembayar,
                    $kdKamar,
                    $namaR,
                    $tarifKamarPerHari,
                    $umurStr,
                    $sObj->nama_sistembayar
                );
            }
        }

        return new JsonResponse([
            'result' => [
                'list_ruangan' => $listRuangan,
                'list_sistembayar' => $listSistemBayar,
                'global' => $dataGlobal,
                'per_ruangan' => $perRuangan,
                'details' => $details
            ],
            'message' => 'Data rekap billing berhasil diambil'
        ], 200);
    }

        /**
     * Hitung Faktur Rekap Global (billing.php)
     */
    
    /**
     * Hitung Faktur Rekap By Sistem Bayar (billingbysistembayar.php)
     */
    private function calculateBillingBySistemBayar($noreg, $pasien, $flagsistembayar, $namaSistemBayar, $tarifKamarPerHari, $umurStr, $tglmasukIgd)
    {
        // 1. Administrasi
        $administrasi = 0;
        $admRow = DB::select("select rs8 as kodesistembayar, rs17 as kelas from rs35x where rs1 = ? and rs3 = 'K1#' order by rs4 asc limit 1", [$noreg]);
        if (!empty($admRow) && $admRow[0]->kodesistembayar == $flagsistembayar) {
            $tarifA1 = DB::table('rs30tarif')->where('rs3', 'A1#')->first();
            if ($tarifA1) {
                $k = $admRow[0]->kelas;
                if ($k == '3') {
                    $administrasi = (float)$tarifA1->rs6 + (float)$tarifA1->rs7;
                } elseif ($k == '2') {
                    $administrasi = (float)$tarifA1->rs8 + (float)$tarifA1->rs9;
                } elseif (in_array($k, ['1', 'IC', 'ICC', 'NICU', 'IN', 'HCU'])) {
                    $administrasi = (float)$tarifA1->rs10 + (float)$tarifA1->rs11;
                } elseif ($k == 'Utama') {
                    $administrasi = (float)$tarifA1->rs12 + (float)$tarifA1->rs13;
                } elseif ($k == 'VIP') {
                    $administrasi = (float)$tarifA1->rs14 + (float)$tarifA1->rs15;
                } elseif ($k == 'VVIP') {
                    $administrasi = (float)$tarifA1->rs16 + (float)$tarifA1->rs17;
                }
            }
        }

        // 2. Akomodasi
        $akomodasiQuery = DB::table('rs35x')
            ->select(
                DB::raw('(rs7+rs14) as biaya'),
                DB::raw('count(*) as jml'),
                DB::raw('sum(rs7+rs14) as subtotal'),
                DB::raw("CASE rs17 
                    WHEN '3' THEN 'Kelas III' 
                    WHEN 'IC' THEN 'Kelas ICU'  
                    WHEN 'ICC' THEN 'Kelas ICCU' 
                    WHEN 'NICU' THEN 'Kelas Nicu' 
                    WHEN 'IN' THEN 'Kelas Intermediate'  
                    WHEN '2' THEN 'Kelas II' 
                    WHEN '1' THEN 'Kelas I'         
                    WHEN 'HCU' THEN 'HCU'
                    WHEN 'Utama' THEN 'Utama'          
                    WHEN 'VIP' THEN 'VIP'      
                    WHEN 'VVIP' THEN 'VVIP' 
                    WHEN 'PS' THEN 'Kelas Presidential Suite'
                    ELSE rs17
                END as kelas")
            )
            ->where('rs3', 'k1#')
            ->where('rs1', $noreg)
            ->where('rs8', $flagsistembayar)
            ->groupBy('rs17', 'rs7', 'rs14');
        $akomodasiList = $akomodasiQuery->get();
        $akomodasiTotal = (float)$akomodasiList->sum('subtotal');

        // 3. Materai Ranap
        $materai = (float)(DB::selectOne("select sum(rs7) as subtotal from rs35 where rs3='M1#' and rs1=? and rs8=?", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 4. Jasa / Tindakan Dokter
        $tindakandokter = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30,rs21 where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1)
            and rs21.rs13='1' and rs73.rs1=? and rs73.rs24=?
            and (rs73.rs22 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 5. Visite / Konsultasi / Oncall Dokter
        $konsuldokter = (float)(DB::selectOne("select sum(rs140.rs4+rs140.rs5) as subtotal
            from rs140,rs21,rs30tarif where rs21.rs1=rs140.rs3 and rs30tarif.rs3=rs140.rs6 and rs140.rs1=? and rs140.rs9=?", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 6. Tindakan Keperawatan
        $tindakanperawat = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30,rs21 where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1)
            and (rs21.rs13='2' or rs21.rs13='3') and rs73.rs1=? and rs73.rs24=?
            and (rs73.rs22 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 7. Asuhan Gizi
        $gizi = (float)(DB::selectOne("select sum(rs202.rs4+rs202.rs5) as subtotal
            from rs202,rs30tarif where rs30tarif.rs1=rs202.rs3 and rs202.rs1=? and rs202.rs9=? and (rs30tarif.rs1='K00013')", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 8. Makan Pasien
        $gizi2 = (float)(DB::selectOne("select sum(rs202.rs4+rs202.rs5) as subtotal
            from rs202,rs30tarif where rs30tarif.rs1=rs202.rs3 and rs202.rs1=? and rs202.rs9=? and (rs30tarif.rs1='K00004' or rs30tarif.rs1='K00003')", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 9. Biaya Oksigen
        $oksigen = (float)(DB::selectOne("select sum((rs205.rs4+rs205.rs5)*rs205.rs6) as subtotal
            from rs205,rs30tarif where rs30tarif.rs1=rs205.rs3 and rs205.rs1=? and rs205.rs9=?", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 10. Jasa Keperawatan
        $keperawatan = (float)(DB::selectOne("select sum(rs203.rs4+rs203.rs5) as subtotal
            from rs203,rs30tarif where rs30tarif.rs1=rs203.rs3 and rs203.rs1=? and rs203.rs9=?", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // 11. Biaya Pelayanan Penunjang
        // Laboratorium
        $laborat = (float)(DB::selectOne("select sum(subtotalx) as subtotal from(
            select sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx from rs49,rs51 where rs49.rs1=rs51.rs4 and rs49.rs21='' and rs51.rs21<>''
            and rs51.rs1=? and rs51.lunas<>'1' and rs51.rs24=?
            and (rs51.rs23 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR')) group by rs51.rs2
            union all
            select ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx from rs49,rs51 where rs49.rs1=rs51.rs4 and rs49.rs21<>'' and rs51.rs21<>''
            and rs51.rs1=? and rs51.lunas<>'1' and rs51.rs24=?
            and (rs51.rs23 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR')) group by rs51.rs2,rs49.rs21
        ) as vx", [$noreg, $flagsistembayar, $noreg, $flagsistembayar])->subtotal ?? 0);

        // Radiologi
        $radiologi = (float)(DB::selectOne("select sum((rs48.rs6+rs48.rs8)*rs48.rs24) as subtotal from rs48,rs47 where rs47.rs1=rs48.rs4
            and rs48.rs1=? and rs48.rs27=?
            and (rs48.rs26 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // Endoscope
        $endoscope = (float)(DB::selectOne("select sum((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotal
            from rs54,rs53 where rs53.rs1=rs54.rs4 and rs54.rs1=? and rs54.rs14=?
            and (rs54.rs15 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // Operasi (rs54)
        $operasi = (float)(DB::selectOne("select sum((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotal 
            from rs54,rs53 where rs53.rs1=rs54.rs4 and rs54.rs1=? 
            and (rs54.rs15 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR')) and rs54.rs14=?", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // Ruang RR (rs73 rs22='OPERASI')
        $ruangrr = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22='OPERASI'", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // Fisioterapi
        $fisioterapi = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22='FISIO'", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // Hemodialisa
        $hemodialisa = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22='PEN005'", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // Penunjang Lain
        $penunjangLainTotal = 0;
        $listPenunjang = DB::select("select * from rs19 where penunjang_lain='1'");
        foreach ($listPenunjang as $pl) {
            $pSub = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
                from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22=?", [$noreg, $flagsistembayar, $pl->rs1])->subtotal ?? 0);
            $penunjangLainTotal += $pSub;
        }

        // Cardio
        $cardio = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22='POL026'", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // EEG
        $eeg = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22='POL024'", [$noreg, $flagsistembayar])->subtotal ?? 0);

        // Biaya Penggunaan Darah
        $darah = (float)(DB::selectOne("select sum(rs12+rs13+biayalain2) as subtotal from rs231 where rs1=? and rs16=? and rs14<>'POL014'", [$noreg, $flagsistembayar])->subtotal ?? 0);

        $penunjangTotal = $laborat + $radiologi + $endoscope + $operasi + $ruangrr + $fisioterapi + $hemodialisa + $penunjangLainTotal + $cardio + $eeg + $darah;

        // 12. Farmasi / Obat
        $farmasi = (float)(DB::selectOne("select round(sum(subtotalx),0) as subtotal from (
            select sum((round(rs38.rs6,0)*rs38.rs8)+rs38.rs10) as subtotalx from rs38,rs32,rs9 where rs32.rs1=rs38.rs4 and rs38.rs1=? and rs38.lunas<>'1' and rs9.rs1=rs38.rs21 and rs38.rs21=? and rs38.rs24<>'IRD'
            union all
            select IF(rs39.rs8>1,sum((round(rs40.rs7,0)*rs40.rs5)),sum((round(rs40.rs7,0)*rs40.rs5))) as subtotalx
            from rs39,rs40,rs32,rs9 where rs39.rs2=rs40.rs2 and rs32.rs1=rs40.rs4 and rs39.rs1=? and rs39.lunas<>'1' and rs9.rs1=rs39.rs16 and rs39.rs16=? and rs39.rs18<>'IRD'
            union all
            select sum((round(rs62.rs6,0)*rs62.rs8)+rs62.rs10) as subtotalx from rs62,rs32,rs9 where rs32.rs1=rs62.rs4 and rs62.rs1=? and rs62.lunas<>'1' and rs9.rs1=rs62.rs21 and rs62.rs21=? and rs62.rs24<>'IRD'
            union all
            select IF(rs63.rs8>1,sum((round(rs64.rs7,0)*rs64.rs5)),sum((round(rs64.rs7,0)*rs64.rs5))) as subtotalx
            from rs63,rs64,rs32,rs9 where rs63.rs2=rs64.rs2 and rs32.rs1=rs64.rs4 and rs63.rs1=? and rs63.lunas<>'1' and rs9.rs1=rs63.rs16 and rs63.rs16=? and rs63.rs18<>'IRD'
            union all
            select sum(rs8) as subtotalx from rs39 where rs1=? and rs18<>'IRD' and rs16=? and rs19='CENTRAL' and lunas<>'1'
            union all
            select sum(rs8) as subtotalx from rs63 where rs1=? and rs18<>'IRD' and rs16=? and rs19='CENTRAL' and lunas<>'1'
        ) as vx", [$noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar])->subtotal ?? 0);

        // 13. Operasi Cito (OK Ranap)
        $irdoperasi = (float)(DB::selectOne("select sum((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotal
            from rs54,rs53 where rs53.rs1=rs54.rs4 and rs54.rs1=? and rs54.rs14=? and rs54.rs15='POL014'", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $irdoperasi2 = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22='OPERASI2'", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $operasiCito = $irdoperasi + $irdoperasi2;

        // 14. Biaya Farmasi / Obat (IRD)
        $farmasiIrd = (float)(DB::selectOne("select round(sum(subtotalx),0) as subtotal from(
            select sum((round(rs38.rs6,0)*rs38.rs8)+rs38.rs10) as subtotalx from rs38,rs32,rs9 where rs32.rs1=rs38.rs4 and rs38.rs1=? and rs38.rs21=? and rs38.lunas<>'1'
            and rs9.rs1=rs38.rs21 and (rs38.rs25='CENTRAL' or rs38.rs25='IGD') and rs38.rs24='IRD'
            union all
            select IF(rs39.rs8>1,sum((round(rs40.rs7,0)*rs40.rs5)),sum((round(rs40.rs7,0)*rs40.rs5))) as subtotalx
            from rs39,rs40,rs32,rs9 where rs39.rs2=rs40.rs2 and rs32.rs1=rs40.rs4 and rs39.rs1=? and rs39.rs16=? and rs39.lunas<>'1'
            and rs9.rs1=rs39.rs16 and (rs39.rs19='CENTRAL' or rs39.rs19='IGD') and rs39.rs18='IRD'
            union all
            select sum((round(rs62.rs6,0)*rs62.rs8)+rs62.rs10) as subtotalx from rs62,rs32,rs9 where rs32.rs1=rs62.rs4 and rs62.rs1=? and rs62.rs21=? and rs62.lunas<>'1'
            and rs9.rs1=rs62.rs21 and (rs62.rs25='CENTRAL' or rs62.rs25='IGD') and rs62.rs24='IRD'
            union all
            select IF(rs63.rs8>1,sum((round(rs64.rs7,0)*rs64.rs5)),sum((round(rs64.rs7,0)*rs64.rs5))) as subtotalx
            from rs63,rs64,rs32,rs9 where rs63.rs2=rs64.rs2 and rs32.rs1=rs64.rs4 and rs63.rs1=? and rs63.rs16=? and rs63.lunas<>'1'
            and rs9.rs1=rs63.rs16 and (rs63.rs19='CENTRAL' or rs63.rs19='IGD') and rs63.rs18='IRD'
            union all
            select sum(rs8) as subtotalx from rs39 where rs1=? and rs16=? and rs18='IRD' and (rs19='CENTRAL' or rs19='IGD')
            union all
            select sum(rs8) as subtotalx from rs63 where rs1=? and rs16=? and rs18='IRD' and (rs19='CENTRAL' or rs19='IGD')
        ) as vx", [$noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar, $noreg, $flagsistembayar])->subtotal ?? 0);

        // 16. IRD
        $irdA2 = (float)(DB::selectOne("select rs35x.rs7 as subtotal from rs35x,rs17 where rs35x.rs1=rs17.rs1 and rs17.rs14=? and rs35x.rs3='A2#' and rs35x.rs1=?", [$flagsistembayar, $noreg])->subtotal ?? 0);
        $irdTindakan = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal from rs73,rs30,rs21 where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1) and rs73.rs1=? and rs73.rs24=? and rs73.rs22='POL014'", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $irdLaborat = (float)(DB::selectOne("select sum(subtotalx) as subtotal from(
            select sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx from rs49,rs51 where rs49.rs1=rs51.rs4 and rs49.rs21='' and rs51.rs21<>''
            and rs51.rs1=? and rs51.rs24=? and rs51.lunas<>'1' and rs51.rs23='POL014' group by rs51.rs2
            union all
            select ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx from rs49,rs51 where rs49.rs1=rs51.rs4 and rs49.rs21<>'' and rs51.rs26='1' and rs51.rs21<>''
            and rs51.rs1=? and rs51.rs24=? and rs51.lunas<>'1' and rs51.rs23='POL014' group by rs51.rs2,rs49.rs21
        ) as vx", [$noreg, $flagsistembayar, $noreg, $flagsistembayar])->subtotal ?? 0);
        $irdLaborat2 = (float)(DB::selectOne("select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1=? and rs73.rs24=? and rs73.rs22='LAB2'", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $irdRadiologi = (float)(DB::selectOne("select sum((rs48.rs6+rs48.rs8)*rs48.rs24) as subtotal from rs48,rs47 where rs47.rs1=rs48.rs4 and rs48.rs1=? and rs48.rs27=? and rs48.rs26='POL014'", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $okIrd = (float)(DB::selectOne("select sum((rs226.rs5+rs226.rs6+rs226.rs7)*rs226.rs8) as subtotal from rs226,rs53 where rs53.rs1=rs226.rs4 and rs226.rs1=? and rs226.rs19=? and rs226.rs15='POL014'", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $darahIrd = (float)(DB::selectOne("select sum(rs12+rs13) as subtotal from rs231 where rs1=? and rs16=? and rs14='POL014'", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $materaiIrd = 0;
        if ($flagsistembayar == 'AR32') {
            $matIrdRow = DB::selectOne("select rs5 as subtotal from rsjr where rs1=? and rs7='IRD'", [$noreg]);
            if ($matIrdRow) $materaiIrd = (float)$matIrdRow->subtotal;
        }
        $ird = $irdA2 + $irdTindakan + $irdLaborat + $irdLaborat2 + $irdRadiologi + $okIrd + $darahIrd + $materaiIrd;

        // Ambulan
        $ambulan = (float)(DB::selectOne("select sum(rs7+rs11) as subtotal from rs35 where rs3='AB#' and rs1=?", [$noreg])->subtotal ?? 0);

        // Grand Total
        $grandTotal = $administrasi + $akomodasiTotal + $materai + $tindakandokter + $konsuldokter + $tindakanperawat + $gizi + $gizi2 + $oksigen + $keperawatan + $penunjangTotal + $farmasi + $operasiCito + $farmasiIrd + $ird + $ambulan;

        // Pembayaran
        $returFarmasi = (float)(DB::selectOne("select round(sum(rs88.rs3*rs88.rs4),0) as subtotal from rs87,rs88,rs32 where rs87.rs1=rs88.rs1 and rs32.rs1=rs88.rs2 and rs87.rs7=? and rs87.rs9=?", [$noreg, $flagsistembayar])->subtotal ?? 0);
        $kurangBayar = $grandTotal - $returFarmasi;

        return [
            'header' => [
                'noreg' => $pasien->noreg,
                'norm' => $pasien->norm,
                'nama' => $pasien->nama,
                'pekerjaan' => $pasien->pekerjaan ?? '',
                'umur' => $umurStr,
                'alamat' => $pasien->alamat ?? '',
                'kelamin' => $pasien->kelamin ?? '',
                'ruangan' => $pasien->ruang_poli ?? '-',
                'kelas' => $pasien->kelas ?? '',
                'ongkos_per_hari' => $tarifKamarPerHari,
                'tglmasuk' => $pasien->tglmasuk ?? '',
                'tglmasuk_igd' => $tglmasukIgd,
                'tglkeluar' => ($pasien->tglkeluar && $pasien->tglkeluar !== '0000-00-00 00:00:00') ? $pasien->tglkeluar : '0000-00-00 00:00:00',
                'dokter' => $pasien->dokter ?? '',
                'sistembayar' => $namaSistemBayar ?: ($pasien->sistembayar ?: '-'),
            ],
            'rincian' => [
                'administrasi' => $administrasi,
                'akomodasi' => [
                    'total' => $akomodasiTotal,
                    'items' => $akomodasiList->map(function ($a) {
                        return [
                            'kelas' => $a->kelas,
                            'jml_hari' => (int)$a->jml,
                            'biaya' => (float)$a->biaya,
                            'subtotal' => (float)$a->subtotal
                        ];
                    })
                ],
                'materai' => $materai,
                'tindakan_dokter' => $tindakandokter,
                'visite' => $konsuldokter,
                'tindakan_keperawatan' => $tindakanperawat,
                'gizi' => $gizi,
                'makan_pasien' => $gizi2,
                'oksigen' => $oksigen,
                'jasa_keperawatan' => $keperawatan,
                'pelayanan_penunjang' => [
                    'total' => $penunjangTotal,
                    'items' => [
                        'laboratorium' => $laborat,
                        'radiologi' => $radiologi,
                        'endoscope' => $endoscope,
                        'operasi' => $operasi,
                        'ruang_rr' => $ruangrr,
                        'fisioterapi' => $fisioterapi,
                        'hemodialisa' => $hemodialisa,
                        'anestesi_luar_ok_icu' => 0,
                        'cardio' => $cardio,
                        'eeg' => $eeg,
                        'biaya_penggunaan_darah' => $darah,
                        'psikologi' => 0,
                        'perawatan_jenazah' => 0,
                        'biaya_ambulan' => $ambulan,
                        'penunjang_lain' => $penunjangLainTotal
                    ]
                ],
                'farmasi' => $farmasi,
                'operasi_cito' => $operasiCito,
                'farmasi_ird' => $farmasiIrd,
                'ird' => $ird
            ],
            'grand_total' => $grandTotal,
            'pembayaran' => [
                'telah_dibayar' => 0,
                'potongan_jasa' => 0,
                'farmasi_telah_dibayar_ranap' => 0,
                'farmasi_telah_dibayar_ird' => 0,
                'retur_farmasi' => $returFarmasi,
                'ird_telah_dibayar' => 0,
                'potongan' => 0,
                'keringanan' => 0,
                'potongan_bpjs' => 0,
                'kurang_bayar' => $kurangBayar
            ]
        ];
    }

    private function calculateBillingGlobal($noreg, $pasien, $tarifKamarPerHari, $umurStr, $tglmasukIgd, $flagsistembayar = null, $namaSistemBayar = null)
    {
        // Administrasi
        $administrasi = 0;
        $sqlAdm = DB::select("select rs8 as kodesistembayar,rs17 as kelas from rs35x where rs1 = ? and rs3='K1#' order by rs4 asc limit 0,1", [$noreg]);
        if (!empty($sqlAdm)) {
            $tarifA1 = DB::table('rs30tarif')->where('rs3', 'A1#')->first();
            if ($tarifA1) {
                $k = $sqlAdm[0]->kelas;
                if ($k == '3') {
                    $administrasi = (float)$tarifA1->rs6 + (float)$tarifA1->rs7;
                } elseif ($k == '2') {
                    $administrasi = (float)$tarifA1->rs8 + (float)$tarifA1->rs9;
                } elseif (in_array($k, ['1', 'IC', 'ICC', 'NICU', 'IN', 'HCU'])) {
                    $administrasi = (float)$tarifA1->rs10 + (float)$tarifA1->rs11;
                } elseif ($k == 'Utama') {
                    $administrasi = (float)$tarifA1->rs12 + (float)$tarifA1->rs13;
                } elseif ($k == 'VIP') {
                    $administrasi = (float)$tarifA1->rs14 + (float)$tarifA1->rs15;
                } elseif ($k == 'VVIP') {
                    $administrasi = (float)$tarifA1->rs16 + (float)$tarifA1->rs17;
                }
            }
        }

        // Akomodasi
        $akomodasiQuery = DB::table('rs35x')
            ->select(
                DB::raw('(rs7+rs14) as biaya'),
                DB::raw('count(*) as jml'),
                DB::raw('sum(rs7+rs14) as subtotal'),
                DB::raw("CASE rs17 
                    WHEN '3' THEN 'Kelas III' 
                    WHEN 'IC' THEN 'Kelas ICU'  
                    WHEN 'ICC' THEN 'Kelas ICCU' 
                    WHEN 'NICU' THEN 'Kelas Nicu' 
                    WHEN 'IN' THEN 'Kelas Intermediate'  
                    WHEN '2' THEN 'Kelas II' 
                    WHEN '1' THEN 'Kelas I'         
                    WHEN 'HCU' THEN 'HCU'
                    WHEN 'Utama' THEN 'Utama'          
                    WHEN 'VIP' THEN 'VIP'      
                    WHEN 'VVIP' THEN 'VVIP' 
                    WHEN 'PS' THEN 'Kelas Presidential Suite'
                    ELSE rs17
                END as kelas")
            )
            ->where('rs3', 'k1#')
            ->where('rs1', $noreg)
            ->groupBy('rs17', 'rs7', 'rs14');
        if ($flagsistembayar && $flagsistembayar !== 'ALL') {
            $akomodasiQuery->where('rs8', $flagsistembayar);
        }
        $akomodasiList = $akomodasiQuery->get();
        $akomodasiTotal = (float)$akomodasiList->sum('subtotal');

        // Materai
        $materaiRow = DB::select("select sum(rs5) as subtotal from rsjr where rs1 = ? and rs7 <> 'IRD'", [$noreg]);
        $materai = $materaiRow ? (float)($materaiRow[0]->subtotal ?? 0) : 0;

        // Tindakan Dokter
        $tindakanDokterRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30,rs21 
            where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1)
              and rs21.rs13='1' and rs73.rs1 = ?
              and rs73.rs22!='POL014' and rs73.rs22!='OPERASI'
        ", [$noreg]);
        $tindakanDokterSubtotal = $tindakanDokterRow ? (float)($tindakanDokterRow[0]->subtotal ?? 0) : 0;

        // Visite Dokter
        $visiteRow = DB::select("
            select sum(rs140.rs4+rs140.rs5) as subtotal 
            from rs140,rs21,rs30tarif
            where rs21.rs1=rs140.rs3 and rs30tarif.rs3=rs140.rs6 and rs140.rs1 = ?
        ", [$noreg]);
        $visiteDokterSubtotal = $visiteRow ? (float)($visiteRow[0]->subtotal ?? 0) : 0;

        // Tindakan Keperawatan
        $tindakanPerawatRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30,rs21 
            where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1)
              and (rs21.rs13='2' or rs21.rs13='3') and rs73.rs1 = ?
              and rs73.rs22!='POL014' and rs73.rs22!='OPERASI'
        ", [$noreg]);
        $tindakanPerawatSubtotal = $tindakanPerawatRow ? (float)($tindakanPerawatRow[0]->subtotal ?? 0) : 0;

        // Asuhan Gizi
        $asuhanGiziRow = DB::select("
            select sum(rs202.rs4+rs202.rs5) as subtotal 
            from rs202,rs30tarif
            where rs30tarif.rs1=rs202.rs3 and rs202.rs1 = ? and rs30tarif.rs1='K00013'
        ", [$noreg]);
        $asuhanGiziSubtotal = $asuhanGiziRow ? (float)($asuhanGiziRow[0]->subtotal ?? 0) : 0;

        // Makan Pasien
        $makanPasienRow = DB::select("
            select sum(rs202.rs4+rs202.rs5) as subtotal 
            from rs202,rs30tarif
            where rs30tarif.rs1=rs202.rs3 and rs202.rs1 = ? and (rs30tarif.rs1='K00004' or rs30tarif.rs1='K00003')
        ", [$noreg]);
        $makanPasienSubtotal = $makanPasienRow ? (float)($makanPasienRow[0]->subtotal ?? 0) : 0;

        // Biaya Oksigen
        $oksigenRow = DB::select("
            select sum((rs205.rs4+rs205.rs5)*rs205.rs6) as subtotal 
            from rs205,rs30tarif
            where rs30tarif.rs1=rs205.rs3 and rs205.rs1 = ?
        ", [$noreg]);
        $oksigenSubtotal = $oksigenRow ? (float)($oksigenRow[0]->subtotal ?? 0) : 0;

        // Jasa Keperawatan
        $jasaKepRow = DB::select("
            select sum(rs203.rs4+rs203.rs5) as subtotal 
            from rs203,rs30tarif
            where rs30tarif.rs1=rs203.rs3 and rs203.rs1 = ?
        ", [$noreg]);
        $jasaKeperawatanSubtotal = $jasaKepRow ? (float)($jasaKepRow[0]->subtotal ?? 0) : 0;

        // Penunjang Global
        $labRow = DB::select("
            select sum(subtotalx) as subtotal from(
                select '' as flag,rs51.rs2 as nota,rs51.rs3 as tgl,rs51.rs4 as kode,rs51.rs8 as kodedokter,rs49.rs2 as keterangan,(rs51.rs6+rs51.rs13) as biaya,
                rs51.rs5 as jml,sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx 
                from rs49,rs51 
                where rs49.rs1=rs51.rs4 and rs49.rs21='' and rs51.rs1 = ? and rs51.rs18<>'' and rs51.rs23!='POL014' 
                group by rs51.rs2
                union all
                select 'x' as flag,rs51.rs2 as nota,rs51.rs3 as tgl,rs51.rs4 as kode,rs51.rs8 as kodedokter,rs49.rs21 as keterangan,(rs51.rs6+rs51.rs13) as biaya,
                rs51.rs5 as jml,((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx 
                from rs49,rs51 
                where rs49.rs1=rs51.rs4 and rs49.rs21<>'' and rs51.rs1 = ? and rs51.rs18<>'' and rs51.rs23!='POL014' 
                group by rs51.rs2,rs49.rs21
            ) as vx
        ", [$noreg, $noreg]);
        $lab1 = $labRow ? (float)($labRow[0]->subtotal ?? 0) : 0;

        $lab2Row = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 
            where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='LAB'
        ", [$noreg]);
        $lab2 = $lab2Row ? (float)($lab2Row[0]->subtotal ?? 0) : 0;
        $laboratoriumTotal = $lab1 + $lab2;

        $radRow = DB::select("
            select sum((rs48.rs6+rs48.rs8)*rs48.rs24) as subtotal 
            from rs48,rs47 
            where rs47.rs1=rs48.rs4 and rs48.rs1 = ? and rs48.rs26!='POL014'
        ", [$noreg]);
        $radiologiTotal = $radRow ? (float)($radRow[0]->subtotal ?? 0) : 0;

        $endoRow = DB::select("
            select sum(subtotal) as subtotal from (
                select sum(rs5) as subtotal from rs246 where rs1 = ?
                union all
                select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
                from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and (rs73.rs22='POL031')
            ) as vendoscope
        ", [$noreg, $noreg]);
        $endoscopyTotal = $endoRow ? (float)($endoRow[0]->subtotal ?? 0) : 0;

        $operasiRow = DB::select("
            select sum(subtotal) as subtotal from (
                select sum((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotal
                from rs54,rs53 where rs53.rs1=rs54.rs4 and rs54.rs1 = ?
                and (rs54.rs15 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','WKKB','KA','ISHK','TR','SKR','ASK','TLP'))
                union all
                select sum((rs226.rs5+rs226.rs6+rs226.rs7)*rs226.rs8) as subtotal
                from rs226,rs53 where rs53.rs1=rs226.rs4 and rs226.rs1 = ?
                and (rs226.rs15 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','WKKB','KA','ISHK','TR','SKR','ASK','TLP'))
            ) as v_operasi
        ", [$noreg, $noreg]);
        $operasiTotal = $operasiRow ? (float)($operasiRow[0]->subtotal ?? 0) : 0;

        $operasi2Row = DB::select("
            select sum(subtotal) as subtotal from (
                select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
                from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='OPERASI'
                union all
                select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
                from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='OPERASIIRD'
            ) as v_tindakan
        ", [$noreg, $noreg]);
        $ruangRrTotal = $operasi2Row ? (float)($operasi2Row[0]->subtotal ?? 0) : 0;

        $fisioRow = DB::select("
            select sum(if((rs73.rs25<>'POL014' and rs73.rs25<>'') or rs73.rs25='',((rs73.rs7+rs73.rs13)*rs73.rs5),0)) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='FISIO'
        ", [$noreg]);
        $fisioterapiTotal = $fisioRow ? (float)($fisioRow[0]->subtotal ?? 0) : 0;

        $hdRow = DB::select("
            select sum(if((rs73.rs25<>'POL014' and rs73.rs25<>'') or rs73.rs25='',((rs73.rs7+rs73.rs13)*rs73.rs5),0)) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='PEN005'
        ", [$noreg]);
        $hemodialisaTotal = $hdRow ? (float)($hdRow[0]->subtotal ?? 0) : 0;

        $penunjangLainList = [];
        $totalPenunjangLainDinamis = 0;
        $masterPenunjangLain = DB::table('rs19')->where('penunjang_lain', '1')->get();
        foreach ($masterPenunjangLain as $mpl) {
            $subMplRow = DB::select("
                select sum(if((rs73.rs25<>'POL014' and rs73.rs25<>'') or rs73.rs25='',((rs73.rs7+rs73.rs13)*rs73.rs5),0)) as subtotal
                from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22 = ?
            ", [$noreg, $mpl->rs1]);
            $subMpl = $subMplRow ? (float)($subMplRow[0]->subtotal ?? 0) : 0;
            $penunjangLainList[] = [
                'kode' => $mpl->rs1,
                'nama' => $mpl->rs2,
                'subtotal' => $subMpl
            ];
            $totalPenunjangLainDinamis += $subMpl;
        }

        $cardioRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and (rs73.rs22='POL026')
        ", [$noreg]);
        $cardioTotal = $cardioRow ? (float)($cardioRow[0]->subtotal ?? 0) : 0;

        $eegRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and (rs73.rs22='POL024')
        ", [$noreg]);
        $eegTotal = $eegRow ? (float)($eegRow[0]->subtotal ?? 0) : 0;

        $psikologiRow = DB::select("
            select sum((psikologi_trans.rs7+psikologi_trans.rs13)*psikologi_trans.rs5) as subtotal
            from psikologi_trans,rs30 where rs30.rs1=psikologi_trans.rs4 and psikologi_trans.rs1 = ?
        ", [$noreg]);
        $psikologiTotal = $psikologiRow ? (float)($psikologiRow[0]->subtotal ?? 0) : 0;

        $darahRow = DB::select("
            select sum(rs12+rs13+biayalain2) as subtotal 
            from rs231 where rs1 = ? and rs14<>'POL014'
        ", [$noreg]);
        $darahTotal = $darahRow ? (float)($darahRow[0]->subtotal ?? 0) : 0;

        $jenasahRow = DB::select("
            select sum(subtotal) as subtotal from(
                select sum(rs275.rs5+rs275.rs6) as subtotal from rs275 where rs275.rs1 = ? and rs275.rs7<>'POL014'
                union all
                select sum(rs273.rs6+rs273.rs7) as subtotal from rs273,rs30 where rs273.rs5=rs30.rs1 and rs273.rs1 = ? and rs273.rs14<>'POL014'
            ) as vx
        ", [$noreg, $noreg]);
        $jenasahTotal = $jenasahRow ? (float)($jenasahRow[0]->subtotal ?? 0) : 0;

        $ambulanRow = DB::select("
            SELECT SUM(subtotal) AS subtotal FROM (
                SELECT SUM(rs2 + rs15 + rs16 + rs17 + rs18 + rs23 + rs26 + rs30) AS subtotal
                FROM rs283 WHERE rs1 = ? AND rs20 <> 'POL014'
                UNION ALL
                SELECT SUM(rs7 + rs11) AS subtotal FROM rs35 WHERE rs3 = 'AB#' AND rs1 = ?
            ) AS v_ambulan
        ", [$noreg, $noreg]);
        $ambulanTotal = $ambulanRow ? (float)($ambulanRow[0]->subtotal ?? 0) : 0;

        $apheresisRow = DB::select("
            select sum(subtotalx) as subtotal from (
                select round(sum(tapheresis.js+tapheresis.jp)) as subtotalx
                from tpermintaanapheresis,tapheresis
                where tpermintaanapheresis.noreg=tapheresis.noreg and tpermintaanapheresis.flag=1 and tpermintaanapheresis.noreg = ?
                group by tpermintaanapheresis.nota_permintaan
                union all
                select round(sum(trans_darahApheresis.rs10+trans_darahApheresis.rs11)) as subtotalx
                from tpermintaanapheresis,trans_darahApheresis
                where tpermintaanapheresis.noreg=trans_darahApheresis.rs1 and tpermintaanapheresis.flag=1 and tpermintaanapheresis.noreg = ?
                group by tpermintaanapheresis.nota_permintaan
            ) as wew
        ", [$noreg, $noreg]);
        $apheresisTotal = $apheresisRow ? (float)($apheresisRow[0]->subtotal ?? 0) : 0;

        $cathlabRow = DB::select("
            select sum(cathlab.js+cathlab.jp) as subtotal
            from cathlab_req
            left join cathlab on cathlab_req.nota=cathlab.nota
            where cathlab_req.noreg = ? and cathlab_req.kdruang !='POL014'
        ", [$noreg]);
        $cathlabTotal = $cathlabRow ? (float)($cathlabRow[0]->subtotal ?? 0) : 0;

        $penunjangKeluarRow = DB::select("
            select sum((harga_sarana+harga_pelayanan)*jumlah) as subtotal
            from lab_keluar
            where noreg = ? and ruangan !='POL014'
        ", [$noreg]);
        $penunjangKeluarTotal = $penunjangKeluarRow ? (float)($penunjangKeluarRow[0]->subtotal ?? 0) : 0;

        $totalPenunjang = $laboratoriumTotal + $radiologiTotal + $endoscopyTotal + $operasiTotal + $ruangRrTotal
            + $fisioterapiTotal + $hemodialisaTotal + $totalPenunjangLainDinamis + $cardioTotal + $eegTotal
            + $psikologiTotal + $darahTotal + $jenasahTotal + $ambulanTotal + $apheresisTotal + $cathlabTotal + $penunjangKeluarTotal;

        // Farmasi Ranap
        $farmasiRsRow = DB::select("
            select round(sum(subtotalx),0) as subtotal from(
                select sum((round(rs38.rs6,0)*rs38.rs8)+rs38.rs10) as subtotalx from rs38,rs32,rs9 where rs32.rs1=rs38.rs4 and rs38.rs1 = ? and rs38.lunas<>'1' and rs9.rs1=rs38.rs21 and (rs38.rs25='CENTRAL') and rs38.rs24<>'IRD'
                union all
                select IF(rs39.rs8>1,sum((round(rs40.rs7,0)*rs40.rs5)),sum((round(rs40.rs7,0)*rs40.rs5))) as subtotalx from rs39,rs40,rs32,rs9 where rs39.rs2=rs40.rs2 and rs32.rs1=rs40.rs4 and rs39.rs1 = ? and rs39.lunas<>'1' and rs9.rs1=rs39.rs16 and (rs39.rs19='CENTRAL') and rs39.rs18<>'IRD'
                union all
                select sum((round(rs62.rs6,0)*rs62.rs8)+rs62.rs10) as subtotalx from rs62,rs32,rs9 where rs32.rs1=rs62.rs4 and rs62.rs1 = ? and rs62.lunas<>'1' and rs9.rs1=rs62.rs21 and (rs62.rs25='CENTRAL') and rs62.rs24<>'IRD'
                union all
                select IF(rs63.rs8>1,sum((round(rs64.rs7,0)*rs64.rs5)),sum((round(rs64.rs7,0)*rs64.rs5))) as subtotalx from rs63,rs64,rs32,rs9 where rs63.rs2=rs64.rs2 and rs32.rs1=rs64.rs4 and rs63.rs1 = ? and rs63.lunas<>'1' and rs9.rs1=rs63.rs16 and (rs63.rs19='CENTRAL') and rs63.rs18<>'IRD'
                union all
                select sum(rs8) as subtotalx from rs39 where rs1 = ? and rs18<>'IRD' and rs19='CENTRAL' and lunas<>'1'
                union all
                select sum(rs8) as subtotalx from rs63 where rs1 = ? and rs18<>'IRD' and rs19='CENTRAL' and lunas<>'1'
            ) as vx
        ", [$noreg, $noreg, $noreg, $noreg, $noreg, $noreg]);
        $farmasiRs = $farmasiRsRow ? (float)($farmasiRsRow[0]->subtotal ?? 0) : 0;

        $farmasiNewRow = DB::connection('farmasi')->select("
            select sum(subtotal) as subtotal from (
                select sum(round(resep_keluar_r.harga_jual*resep_keluar_r.jumlah+resep_keluar_r.nilai_r)) as subtotal 
                from resep_keluar_h 
                left join resep_keluar_r on resep_keluar_h.noresep=resep_keluar_r.noresep 
                where resep_keluar_h.noreg = ? and resep_keluar_h.ruangan!='POL014' and (resep_keluar_h.depo='Gd-04010102' or resep_keluar_h.depo='Gd-04010103')
                union all
                select sum(round(resep_keluar_racikan_r.harga_jual*resep_keluar_racikan_r.jumlah)+(resep_keluar_racikan_r.nilai_r)) as subtotal 
                from resep_keluar_h 
                left join resep_keluar_racikan_r on resep_keluar_h.noresep=resep_keluar_racikan_r.noresep 
                where resep_keluar_h.noreg = ? and resep_keluar_h.ruangan!='POL014' and (resep_keluar_h.depo='Gd-04010102' or resep_keluar_h.depo='Gd-04010103')
            ) as wew
        ", [$noreg, $noreg]);
        $farmasiNew = $farmasiNewRow ? (float)($farmasiNewRow[0]->subtotal ?? 0) : 0;
        $farmasiTotal = $farmasiRs + $farmasiNew;

        // Operasi Cito (OK Ranap)
        $irdOperasiRow = DB::select("
            select sum((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotal
            from rs54,rs53 where rs53.rs1=rs54.rs4 and rs54.rs1 = ? and rs54.rs15='POL014'
        ", [$noreg]);
        $irdOperasi1 = $irdOperasiRow ? (float)($irdOperasiRow[0]->subtotal ?? 0) : 0;

        $irdOperasi2Row = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='OPERASI2'
        ", [$noreg]);
        $irdOperasi2 = $irdOperasi2Row ? (float)($irdOperasi2Row[0]->subtotal ?? 0) : 0;
        $operasiCitoTotal = $irdOperasi1 + $irdOperasi2;

        // Farmasi IRD
        $farmasiIrdRsRow = DB::select("
            select round(sum(subtotalx),0) as subtotal from(
                select sum((round(rs38.rs6,0)*rs38.rs8)+rs38.rs10) as subtotalx from rs38,rs32,rs9 where rs32.rs1=rs38.rs4 and rs38.rs1 = ? and rs38.lunas<>'1' and rs9.rs1=rs38.rs21 and (rs38.rs25='CENTRAL' or rs38.rs25='IGD') and rs38.rs24='IRD'
                union all
                select IF(rs39.rs8>1,sum((round(rs40.rs7,0)*rs40.rs5)),sum((round(rs40.rs7,0)*rs40.rs5))) as subtotalx from rs39,rs40,rs32,rs9 where rs39.rs2=rs40.rs2 and rs32.rs1=rs40.rs4 and rs39.rs1 = ? and rs39.lunas<>'1' and rs9.rs1=rs39.rs16 and (rs39.rs19='CENTRAL' or rs39.rs19='IGD') and rs39.rs18='IRD'
                union all
                select sum((round(rs62.rs6,0)*rs62.rs8)+rs62.rs10) as subtotalx from rs62,rs32,rs9 where rs32.rs1=rs62.rs4 and rs62.rs1 = ? and rs62.lunas<>'1' and rs9.rs1=rs62.rs21 and (rs62.rs25='CENTRAL' or rs62.rs25='IGD') and rs62.rs24='IRD'
                union all
                select IF(rs63.rs8>1,sum((round(rs64.rs7,0)*rs64.rs5)),sum((round(rs64.rs7,0)*rs64.rs5))) as subtotalx from rs63,rs64,rs32,rs9 where rs63.rs2=rs64.rs2 and rs32.rs1=rs64.rs4 and rs63.rs1 = ? and rs63.lunas<>'1' and rs9.rs1=rs63.rs16 and (rs63.rs19='CENTRAL' or rs63.rs19='IGD') and rs63.rs18='IRD'
                union all
                select sum(rs8) as subtotalx from rs39 where rs1 = ? and rs18='IRD' and (rs19='CENTRAL' or rs19='IGD')
                union all
                select sum(rs8) as subtotalx from rs63 where rs1 = ? and rs18='IRD' and (rs19='CENTRAL' or rs19='IGD')
            ) as vx
        ", [$noreg, $noreg, $noreg, $noreg, $noreg, $noreg]);
        $farmasiIrdRs = $farmasiIrdRsRow ? (float)($farmasiIrdRsRow[0]->subtotal ?? 0) : 0;

        $farmasiIrdNewRow = DB::connection('farmasi')->select("
            select sum(subtotal) as subtotal from (
                select round(sum(resep_keluar_r.harga_jual*resep_keluar_r.jumlah+resep_keluar_r.nilai_r)) as subtotal 
                from resep_keluar_h 
                left join resep_keluar_r on resep_keluar_h.noresep=resep_keluar_r.noresep 
                where resep_keluar_h.noreg = ? and resep_keluar_h.ruangan='POL014' and (resep_keluar_h.depo='Gd-02010104' or resep_keluar_h.depo='Gd-04010103')
                union all
                select round(sum(resep_keluar_racikan_r.harga_jual*resep_keluar_racikan_r.jumlah)+(resep_keluar_racikan_r.nilai_r)) as subtotal 
                from resep_keluar_h 
                left join resep_keluar_racikan_r on resep_keluar_h.noresep=resep_keluar_racikan_r.noresep 
                where resep_keluar_h.noreg = ? and resep_keluar_h.ruangan='POL014' and (resep_keluar_h.depo='Gd-02010104' or resep_keluar_h.depo='Gd-04010103')
            ) as wew
        ", [$noreg, $noreg]);
        $farmasiIrdNew = $farmasiIrdNewRow ? (float)($farmasiIrdNewRow[0]->subtotal ?? 0) : 0;
        $farmasiIrdTotal = $farmasiIrdRs + $farmasiIrdNew;

        // IRD Global
        $ird = 0;
        $irdAdmRow = DB::select("select rs7 as subtotal from rs35x where rs3='A2#' and rs1 = ?", [$noreg]);
        $ird += $irdAdmRow ? (float)($irdAdmRow[0]->subtotal ?? 0) : 0;

        $irdTindakanRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30,rs21 
            where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1)
              and rs73.rs1 = ? and rs73.rs22='POL014'
        ", [$noreg]);
        $ird += $irdTindakanRow ? (float)($irdTindakanRow[0]->subtotal ?? 0) : 0;

        $irdLabRow = DB::select("
            select sum(subtotalx) as subtotal from(
                select ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx 
                from rs49,rs51 
                where rs49.rs1=rs51.rs4 and rs49.rs21='' and rs51.rs1 = ? and rs51.lunas<>'1' and rs51.rs18<>'' and rs51.rs23='POL014'
                union all
                select ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx 
                from rs49,rs51 
                where rs49.rs1=rs51.rs4 and rs49.rs21<>'' and rs51.rs1 = ? and rs51.lunas<>'1' and rs51.rs18<>'' and rs51.rs23='POL014' 
                group by rs51.rs2,rs49.rs21
            ) as vx
        ", [$noreg, $noreg]);
        $ird += $irdLabRow ? (float)($irdLabRow[0]->subtotal ?? 0) : 0;

        $irdLab2Row = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='LAB2'
        ", [$noreg]);
        $ird += $irdLab2Row ? (float)($irdLab2Row[0]->subtotal ?? 0) : 0;

        $irdRadRow = DB::select("
            select sum((rs48.rs6+rs48.rs8)*rs48.rs24) as subtotal 
            from rs48,rs47 where rs47.rs1=rs48.rs4 and rs48.rs1 = ? and rs48.rs26='POL014'
        ", [$noreg]);
        $ird += $irdRadRow ? (float)($irdRadRow[0]->subtotal ?? 0) : 0;

        $irdOperasi2xRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='OPERASIIRD2'
        ", [$noreg]);
        $ird += $irdOperasi2xRow ? (float)($irdOperasi2xRow[0]->subtotal ?? 0) : 0;

        $okIrdRow = DB::select("
            select sum((rs226.rs5+rs226.rs6+rs226.rs7)*rs226.rs8) as subtotal
            from rs226,rs53 where rs53.rs1=rs226.rs4 and rs226.rs1 = ? and rs226.rs15='POL014'
        ", [$noreg]);
        $ird += $okIrdRow ? (float)($okIrdRow[0]->subtotal ?? 0) : 0;

        $darahIrdRow = DB::select("
            select sum(rs12+rs13+biayalain2) as subtotal 
            from rs231 where rs1 = ? and rs14='POL014'
        ", [$noreg]);
        $ird += $darahIrdRow ? (float)($darahIrdRow[0]->subtotal ?? 0) : 0;

        $materaiIrdRow = DB::select("select sum(rs5) as subtotal from rsjr where rs1 = ? and rs7='IRD'", [$noreg]);
        $ird += $materaiIrdRow ? (float)($materaiIrdRow[0]->subtotal ?? 0) : 0;

        $jenasahIrdRow = DB::select("
            select sum(subtotal) as subtotal from(
                select sum(rs275.rs5+rs275.rs6) as subtotal from rs275 where rs275.rs1 = ? and rs275.rs7='POL014'
                union all
                select sum(rs273.rs6+rs273.rs7) as subtotal from rs273,rs30 where rs273.rs5=rs30.rs1 and rs273.rs1 = ? and rs273.rs14='POL014'
            ) as vx
        ", [$noreg, $noreg]);
        $ird += $jenasahIrdRow ? (float)($jenasahIrdRow[0]->subtotal ?? 0) : 0;

        $ambulanIrdRow = DB::select("
            select sum(rs2+rs15+rs16+rs17+rs18+rs23+rs26) as subtotal 
            from rs283 where rs1 = ? and rs20='POL014'
        ", [$noreg]);
        $ird += $ambulanIrdRow ? (float)($ambulanIrdRow[0]->subtotal ?? 0) : 0;

        $irdHdRow = DB::select("
            select sum(if(rs73.rs25='POL014',((rs73.rs7+rs73.rs13)*rs73.rs5),0)) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='PEN005'
        ", [$noreg]);
        $ird += $irdHdRow ? (float)($irdHdRow[0]->subtotal ?? 0) : 0;

        foreach ($masterPenunjangLain as $mpl) {
            $mplIrdRow = DB::select("
                select sum(if(rs73.rs25='POL014',((rs73.rs7+rs73.rs13)*rs73.rs5),0)) as subtotal
                from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22 = ?
            ", [$noreg, $mpl->rs1]);
            $ird += $mplIrdRow ? (float)($mplIrdRow[0]->subtotal ?? 0) : 0;
        }

        $irdFisioRow = DB::select("
            select sum(if(rs73.rs25='POL014',((rs73.rs7+rs73.rs13)*rs73.rs5),0)) as subtotal
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22='FISIO'
        ", [$noreg]);
        $ird += $irdFisioRow ? (float)($irdFisioRow[0]->subtotal ?? 0) : 0;

        $penunjangKeluarIrdRow = DB::select("
            select sum((harga_sarana+harga_pelayanan)*jumlah) as subtotal
            from lab_keluar
            where noreg = ? and ruangan ='POL014'
        ", [$noreg]);
        $ird += $penunjangKeluarIrdRow ? (float)($penunjangKeluarIrdRow[0]->subtotal ?? 0) : 0;

        $irdTotal = $ird;

        // GRAND TOTAL
        $grandTotal = $administrasi
            + $akomodasiTotal
            + $materai
            + $tindakanDokterSubtotal
            + $visiteDokterSubtotal
            + $tindakanPerawatSubtotal
            + $asuhanGiziSubtotal
            + $makanPasienSubtotal
            + $oksigenSubtotal
            + $jasaKeperawatanSubtotal
            + $totalPenunjang
            + $farmasiTotal
            + $operasiCitoTotal
            + $farmasiIrdTotal
            + $irdTotal;

        // PEMBAYARAN & POTONGAN
        $panjer = (float)(DB::select("select sum(rs7+rs11) as subtotal from rs35 where rs3='UM#' and rs1 = ?", [$noreg])[0]->subtotal ?? 0);
        $potongan = (float)(DB::select("select sum(rs7+rs11) as subtotal from rs35 where rs3='DS#' and rs1 = ?", [$noreg])[0]->subtotal ?? 0);
        $potonganJasaRaharja = (float)(DB::select("select sum(rs7+rs11) as subtotal from rs35 where rs3='JS#' and rs1 = ?", [$noreg])[0]->subtotal ?? 0);
        $keringanan = (float)(DB::select("select sum(rs7+rs11) as subtotal from rs35 where rs3='#KR' and rs1 = ?", [$noreg])[0]->subtotal ?? 0);
        $telahdibayarpelunasaan = (float)(DB::select("select sum(rs7+rs11) as subtotal from rs35 where rs3='PL#' and rs1 = ?", [$noreg])[0]->subtotal ?? 0);

        $telahdibayar = (float)(DB::select("
            select sum(subtotalx) as subtotal from (
                select sum((rs38.rs6*rs38.rs8)+rs38.rs10) as subtotalx from rs38,rs32,rs9 where rs32.rs1=rs38.rs4 and rs38.rs1 = ? and rs38.lunas='1' and rs9.rs1=rs38.rs21 and (rs38.rs25='CENTRAL' or rs38.rs25='IGD') and rs38.rs24='IRD'
                union all
                select IF(rs39.rs8>1,sum((rs40.rs7*rs40.rs5)+rs39.rs8),sum((rs40.rs7*rs40.rs5))) as subtotalx from rs39,rs40,rs32,rs9 where rs39.rs2=rs40.rs2 and rs32.rs1=rs40.rs4 and rs39.rs1 = ? and rs39.lunas='1' and rs9.rs1=rs39.rs16 and (rs39.rs19='CENTRAL' or rs39.rs19='IGD') and rs39.rs18='IRD'
                union all
                select sum((rs62.rs6*rs62.rs8)+rs62.rs10) as subtotalx from rs62,rs32,rs9 where rs32.rs1=rs62.rs4 and rs62.rs1 = ? and rs62.lunas='1' and rs9.rs1=rs62.rs21 and (rs62.rs25='CENTRAL' or rs62.rs25='IGD') and rs62.rs24='IRD'
                union all
                select IF(rs63.rs8>1,sum((rs64.rs7*rs64.rs5)+rs63.rs8),sum((rs64.rs7*rs64.rs5))) as subtotalx from rs63,rs64,rs32,rs9 where rs63.rs2=rs64.rs2 and rs32.rs1=rs64.rs4 and rs63.rs1 = ? and rs63.lunas='1' and rs9.rs1=rs63.rs16 and (rs63.rs19='CENTRAL' or rs63.rs19='IGD') and rs63.rs18='IRD'
            ) as vx
        ", [$noreg, $noreg, $noreg, $noreg])[0]->subtotal ?? 0);

        $telahdibayarx = (float)(DB::select("
            select sum(subtotalx) as subtotal from(
                select sum((rs38.rs6*rs38.rs8)+rs38.rs10) as subtotalx from rs38,rs32,rs9 where rs32.rs1=rs38.rs4 and rs38.rs1 = ? and rs38.lunas='1' and rs9.rs1=rs38.rs21 and (rs38.rs25='CENTRAL') and rs38.rs24<>'IRD'
                union all
                select IF(rs39.rs8>1,sum((rs40.rs7*rs40.rs5)+rs39.rs8),sum((rs40.rs7*rs40.rs5))) as subtotalx from rs39,rs40,rs32,rs9 where rs39.rs2=rs40.rs2 and rs32.rs1=rs40.rs4 and rs39.rs1 = ? and rs39.lunas='1' and rs9.rs1=rs39.rs16 and (rs39.rs19='CENTRAL') and rs39.rs18<>'IRD'
                union all
                select sum((rs62.rs6*rs62.rs8)+rs62.rs10) as subtotalx from rs62,rs32,rs9 where rs32.rs1=rs62.rs4 and rs62.rs1 = ? and rs62.lunas='1' and rs9.rs1=rs62.rs21 and (rs62.rs25='CENTRAL') and rs62.rs24<>'IRD'
                union all
                select IF(rs63.rs8>1,sum((rs64.rs7*rs64.rs5)+rs63.rs8),sum((rs64.rs7*rs64.rs5))) as subtotalx from rs63,rs64,rs32,rs9 where rs63.rs2=rs64.rs2 and rs32.rs1=rs64.rs4 and rs63.rs1 = ? and rs63.lunas='1' and rs9.rs1=rs63.rs16 and (rs63.rs19='CENTRAL') and rs63.rs18<>'IRD'
            ) as vx
        ", [$noreg, $noreg, $noreg, $noreg])[0]->subtotal ?? 0);

        $farmasiretur = (float)(DB::select("
            select sum(rs88.rs3*rs88.rs4) as subtotal 
            from rs87,rs88,rs32 
            where rs87.rs1=rs88.rs1 and rs32.rs1=rs88.rs2 and rs87.rs7 = ?
        ", [$noreg])[0]->subtotal ?? 0);

        $potonganbpjs = (float)(DB::select("select sum(rs7+rs11) as subtotal from rs35 where rs3='BP#' and rs1 = ?", [$noreg])[0]->subtotal ?? 0);

        $potonganJasaDokter = (float)(DB::select("
            select if(potongan_jasa.jenis_tarif='jp,',sum(rs73.rs13*rs73.rs5),0) as total 
            from potongan_jasa,rs73,rs21
            where potongan_jasa.id_trans=rs73.id and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1) and rs21.rs13='1'
              and potongan_jasa.noreg = ? and potongan_jasa.jenis='tindakan_dokter' and rs73.rs22!='POL014' and rs73.rs22!='OPERASI'
            group by potongan_jasa.noreg
        ", [$noreg])[0]->total ?? 0);

        $potonganJasaVisite = (float)(DB::select("
            select if(potongan_jasa.jenis_tarif='jp,',sum(rs140.rs5),0) as total 
            from potongan_jasa,rs140
            where potongan_jasa.id_trans=rs140.id and potongan_jasa.noreg = ? and potongan_jasa.jenis='visite'
            group by potongan_jasa.noreg
        ", [$noreg])[0]->total ?? 0);

        $potonganJasaPerawat = (float)(DB::select("
            select if(potongan_jasa.jenis_tarif='jp,',sum(rs73.rs13*rs73.rs5),0) as total 
            from potongan_jasa,rs73,rs21
            where potongan_jasa.id_trans=rs73.id and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1) and (rs21.rs13='2' or rs21.rs13='3')
              and potongan_jasa.noreg = ? and potongan_jasa.jenis='tindakan_keperawatan' and rs73.rs22!='POL014' and rs73.rs22!='OPERASI'
            group by potongan_jasa.noreg
        ", [$noreg])[0]->total ?? 0);

        $potonganJasaKep = (float)(DB::select("
            select if(potongan_jasa.jenis_tarif='jp,',sum(rs203.rs5),0) as total 
            from potongan_jasa,rs203
            where potongan_jasa.id_trans=rs203.id and potongan_jasa.noreg = ? and potongan_jasa.jenis='keperawatan'
            group by potongan_jasa.noreg
        ", [$noreg])[0]->total ?? 0);

        $totalPotonganJasa = $potonganJasaDokter + $potonganJasaVisite + $potonganJasaPerawat + $potonganJasaKep;

        $harusbayar = $grandTotal - $panjer - $telahdibayar - $telahdibayarx - $potongan - $potonganJasaRaharja - $potonganbpjs - $keringanan - $telahdibayarpelunasaan - $farmasiretur - $totalPotonganJasa;

        $sharing3item = (float)(DB::select("select sum(rs228.rs9) as subtotal from rs228 where rs228.rs1 = ?", [$noreg])[0]->subtotal ?? 0);
        if ($potonganbpjs >= $grandTotal) {
            $kurangbayar = $sharing3item;
        } else {
            if ($potonganJasaRaharja > $grandTotal) {
                $kurangbayar = 0;
            } else {
                $kurangbayar = $harusbayar;
            }
        }

        return [
            'header' => [
                'noreg' => $pasien->noreg,
                'norm' => $pasien->norm,
                'nama' => $pasien->nama,
                'pekerjaan' => $pasien->pekerjaan ?? '',
                'umur' => $umurStr,
                'alamat' => $pasien->alamat ?? '',
                'kelamin' => $pasien->kelamin,
                'ruangan' => $pasien->ruang_poli,
                'kelas' => $pasien->kelas,
                'ongkos_per_hari' => $tarifKamarPerHari,
                'tglmasuk' => $pasien->tglmasuk,
                'tglmasuk_igd' => $tglmasukIgd,
                'tglkeluar' => ($pasien->tglkeluar && $pasien->tglkeluar !== '0000-00-00 00:00:00') ? $pasien->tglkeluar : '0000-00-00 00:00:00',
                'dokter' => $pasien->dokter ?? '',
                'sistembayar' => ($flagsistembayar && $flagsistembayar !== 'ALL' && $namaSistemBayar) ? $namaSistemBayar : ($pasien->sistembayar ?: '-'),
            ],
            'rincian' => [
                'administrasi' => $administrasi,
                'akomodasi' => [
                    'total' => $akomodasiTotal,
                    'items' => $akomodasiList
                ],
                'materai' => $materai,
                'tindakan_dokter' => $tindakanDokterSubtotal,
                'visite_dokter' => $visiteDokterSubtotal,
                'tindakan_perawat' => $tindakanPerawatSubtotal,
                'asuhan_gizi' => $asuhanGiziSubtotal,
                'makan_pasien' => $makanPasienSubtotal,
                'oksigen' => $oksigenSubtotal,
                'jasa_keperawatan' => $jasaKeperawatanSubtotal,
                'penunjang' => [
                    'total' => $totalPenunjang,
                    'laboratorium' => $laboratoriumTotal,
                    'radiologi' => $radiologiTotal,
                    'endoscope' => $endoscopyTotal,
                    'operasi' => $operasiTotal,
                    'ruang_rr' => $ruangRrTotal,
                    'fisioterapi' => $fisioterapiTotal,
                    'hemodialisa' => $hemodialisaTotal,
                    'cardio' => $cardioTotal,
                    'eeg' => $eegTotal,
                    'psikologi' => $psikologiTotal,
                    'penggunaan_darah' => $darahTotal,
                    'jenasah' => $jenasahTotal,
                    'ambulan' => $ambulanTotal,
                    'apheresis' => $apheresisTotal,
                    'cathlab' => $cathlabTotal,
                    'penunjang_keluar' => $penunjangKeluarTotal,
                    'penunjang_lain' => $penunjangLainList,
                ],
                'farmasi' => $farmasiTotal,
                'operasi_cito' => $operasiCitoTotal,
                'farmasi_ird' => $farmasiIrdTotal,
                'ird' => $irdTotal
            ],
            'pembayaran' => [
                'telah_dibayar' => $panjer,
                'potongan_jasa' => $totalPotonganJasa,
                'farmasi_telah_dibayar_ranap' => $telahdibayarx,
                'farmasi_telah_dibayar_ird' => $telahdibayar,
                'retur_farmasi' => $farmasiretur,
                'ird_telah_dibayar' => 0,
                'potongan' => $potongan,
                'potongan_jasa_raharja' => $potonganJasaRaharja,
                'keringanan' => $keringanan,
                'potongan_bpjs' => $potonganbpjs,
                'kurang_bayar' => $kurangbayar
            ],
            'grand_total' => $grandTotal
        ];
    }

    /**
     * Hitung Faktur Rekap Per Ruangan (billingbyruanganbysistembayar.php)
     */
    private function calculateBillingPerRuangan($noreg, $pasien, $flagruangan, $flagsistembayar, $flagruangankelas, $namaRuangan, $tarifKamarPerHari, $umurStr, $namaSistemBayar = null)
    {
        // 1. Administrasi
        $administrasi = 0;
        $admRow = DB::select("select rs8 as kodesistembayar, rs17 as kelas from rs35x where rs1 = ? and rs3 = 'K1#' order by rs4 asc limit 1", [$noreg]);
        if (!empty($admRow)) {
            if ($admRow[0]->kodesistembayar == $flagruangan) {
                $tarifA1 = DB::table('rs30tarif')->where('rs3', 'A1#')->first();
                if ($tarifA1) {
                    $k = $admRow[0]->kelas;
                    if ($k == '3') {
                        $administrasi = (float)$tarifA1->rs6 + (float)$tarifA1->rs7;
                    } elseif ($k == '2') {
                        $administrasi = (float)$tarifA1->rs8 + (float)$tarifA1->rs9;
                    } elseif (in_array($k, ['1', 'IC', 'ICC', 'NICU', 'IN', 'HCU'])) {
                        $administrasi = (float)$tarifA1->rs10 + (float)$tarifA1->rs11;
                    } elseif ($k == 'Utama') {
                        $administrasi = (float)$tarifA1->rs12 + (float)$tarifA1->rs13;
                    } elseif ($k == 'VIP') {
                        $administrasi = (float)$tarifA1->rs14 + (float)$tarifA1->rs15;
                    } elseif ($k == 'VVIP') {
                        $administrasi = (float)$tarifA1->rs16 + (float)$tarifA1->rs17;
                    }
                }
            }
        }

        // 2. Akomodasi
        $akomodasiQuery = DB::table('rs35x')
            ->select(
                DB::raw('(rs7+rs14) as biaya'),
                DB::raw('count(*) as jml'),
                DB::raw('sum(rs7+rs14) as subtotal'),
                DB::raw("CASE rs17 
                    WHEN '3' THEN 'Kelas III' 
                    WHEN 'IC' THEN 'Kelas ICU'  
                    WHEN 'ICC' THEN 'Kelas ICCU' 
                    WHEN 'NICU' THEN 'Kelas Nicu' 
                    WHEN 'IN' THEN 'Kelas Intermediate'  
                    WHEN '2' THEN 'Kelas II' 
                    WHEN '1' THEN 'Kelas I'         
                    WHEN 'HCU' THEN 'HCU'
                    WHEN 'Utama' THEN 'Utama'          
                    WHEN 'VIP' THEN 'VIP'      
                    WHEN 'VVIP' THEN 'VVIP' 
                    WHEN 'PS' THEN 'Kelas Presidential Suite'
                    ELSE rs17
                END as kelas")
            )
            ->where('rs3', 'k1#')
            ->where('rs1', $noreg)
            ->where('rs16', $flagruangan)
            ->groupBy('rs17', 'rs7', 'rs14');
        if ($flagsistembayar && $flagsistembayar !== 'ALL') {
            $akomodasiQuery->where('rs8', $flagsistembayar);
        }
        $akomodasiList = $akomodasiQuery->get();
        $akomodasiTotal = (float)$akomodasiList->sum('subtotal');

        // 3. Jasa Tindakan Dokter
        $tindakanDokterRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
            from rs73,rs30,rs21 where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1) 
            and rs21.rs13='1' and rs73.rs1 = ? and rs73.rs22 = ?
            and (rs73.rs22 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))
        ", [$noreg, $flagruangan]);
        $tindakanDokterSubtotal = $tindakanDokterRow ? (float)($tindakanDokterRow[0]->subtotal ?? 0) : 0;

        // 4. Visite Dokter
        $visiteRow = DB::select("
            select sum(rs140.rs4+rs140.rs5) as subtotal from rs140,rs21,rs30tarif 
            where rs21.rs1=rs140.rs3 and rs30tarif.rs3=rs140.rs6 and rs140.rs1 = ? and rs140.rs8 = ?
        ", [$noreg, $flagruangan]);
        $visiteDokterSubtotal = $visiteRow ? (float)($visiteRow[0]->subtotal ?? 0) : 0;

        // 5. Tindakan Keperawatan
        $tindakanPerawatRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
            from rs73,rs30,rs21 where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1) 
            and (rs21.rs13='2' or rs21.rs13='3') and rs73.rs1 = ? and rs73.rs22 = ?
            and (rs73.rs22 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'))
        ", [$noreg, $flagruangan]);
        $tindakanPerawatSubtotal = $tindakanPerawatRow ? (float)($tindakanPerawatRow[0]->subtotal ?? 0) : 0;

        // 6. Asuhan Gizi
        $asuhanGiziRow = DB::select("
            select sum(rs202.rs4+rs202.rs5) as subtotal from rs202,rs30tarif 
            where rs30tarif.rs1=rs202.rs3 and rs202.rs1 = ? and rs202.rs8 = ? and (rs30tarif.rs1='K00013')
        ", [$noreg, $flagruangan]);
        $asuhanGiziSubtotal = $asuhanGiziRow ? (float)($asuhanGiziRow[0]->subtotal ?? 0) : 0;

        // 7. Makan Pasien
        $makanPasienRow = DB::select("
            select sum(rs202.rs4+rs202.rs5) as subtotal from rs202,rs30tarif 
            where rs30tarif.rs1=rs202.rs3 and rs202.rs1 = ? and rs202.rs8 = ? and (rs30tarif.rs1='K00004' or rs30tarif.rs1='K00003')
        ", [$noreg, $flagruangan]);
        $makanPasienSubtotal = $makanPasienRow ? (float)($makanPasienRow[0]->subtotal ?? 0) : 0;

        // 8. Oksigen
        $oksigenRow = DB::select("
            select sum((rs205.rs4+rs205.rs5)*rs205.rs6) as subtotal from rs205,rs30tarif 
            where rs30tarif.rs1=rs205.rs3 and rs205.rs1 = ? and rs205.rs8 = ?
        ", [$noreg, $flagruangan]);
        $oksigenSubtotal = $oksigenRow ? (float)($oksigenRow[0]->subtotal ?? 0) : 0;

        // 9. Jasa Keperawatan
        $jasaKepRow = DB::select("
            select sum(rs203.rs4+rs203.rs5) as subtotal 
            from rs203,rs30tarif where rs30tarif.rs1=rs203.rs3 and rs203.rs1 = ? and rs203.rs8 = ?
        ", [$noreg, $flagruangan]);
        $jasaKeperawatanSubtotal = $jasaKepRow ? (float)($jasaKepRow[0]->subtotal ?? 0) : 0;

        // 10. Penunjang Ruangan
        $labRow = DB::select("
            select sum(subtotalx) as subtotal from(
                select '' as flag,rs51.rs2 as nota,rs51.rs3 as tgl,rs51.rs4 as kode,rs51.rs8 as kodedokter,rs49.rs2 as keterangan,(rs51.rs6+rs51.rs13) as biaya,
                rs51.rs5 as jml,sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx from rs49,rs51 where rs49.rs1=rs51.rs4 and rs49.rs21=''
                and rs51.rs1 = ? and rs51.lunas<>'1' and rs51.rs23 = ? and rs51.rs21<>''
                and (rs51.rs23 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR')) group by rs51.rs2
                union all
                select 'x' as flag,rs51.rs2 as nota,rs51.rs3 as tgl,rs51.rs4 as kode,rs51.rs8 as kodedokter,rs49.rs21 as keterangan,(rs51.rs6+rs51.rs13) as biaya,
                rs51.rs5 as jml,((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx from rs49,rs51 where rs49.rs1=rs51.rs4 and rs49.rs21<>''
                and rs51.rs1 = ? and rs51.lunas<>'1' and rs51.rs23 = ? and rs51.rs21<>''
                and (rs51.rs23 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR')) group by rs51.rs2,rs49.rs21			
            ) as vx
        ", [$noreg, $flagruangan, $noreg, $flagruangan]);
        $lab1 = $labRow ? (float)($labRow[0]->subtotal ?? 0) : 0;

        $lab2Row = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22 = ? and rs73.rs22='LAB'
        ", [$noreg, $flagruangan]);
        $lab2 = $lab2Row ? (float)($lab2Row[0]->subtotal ?? 0) : 0;
        $laboratoriumTotal = $lab1 + $lab2;

        $radRow = DB::select("
            select sum((rs48.rs6+rs48.rs8)*rs48.rs24) as subtotal from rs48,rs47 where rs47.rs1=rs48.rs4 
            and rs48.rs1 = ? 
            and (rs48.rs26 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR')) and rs48.rs26 = ?
        ", [$noreg, $flagruangan]);
        $radiologiTotal = $radRow ? (float)($radRow[0]->subtotal ?? 0) : 0;

        $op1Row = DB::select("
            select sum((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotal 
            from rs54,rs53 where rs53.rs1=rs54.rs4 and rs54.rs1 = ? 
            and (rs54.rs15 in ('BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR')) and rs54.rs15 = ?
        ", [$noreg, $flagruangan]);
        $op1 = $op1Row ? (float)($op1Row[0]->subtotal ?? 0) : 0;

        $op2Row = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22 = ? and rs73.rs22='OPERASI'
        ", [$noreg, $flagruangan]);
        $op2 = $op2Row ? (float)($op2Row[0]->subtotal ?? 0) : 0;
        $operasiTotal = $op1 + $op2;

        $penunjangLainRow = DB::select("
            select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
            from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? and rs73.rs22 = ? and (rs73.rs22='POL024' or rs73.rs22='POL026' or rs73.rs22='PEN005')
        ", [$noreg, $flagruangan]);
        $hemodialisaTotal = $penunjangLainRow ? (float)($penunjangLainRow[0]->subtotal ?? 0) : 0;

        $penunjangLainList = [];
        $totalPenunjangLainDinamis = 0;
        $masterPenunjangLain = DB::table('rs19')->where('penunjang_lain', '1')->get();
        foreach ($masterPenunjangLain as $mpl) {
            $subMplRow = DB::select("
                select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
                from rs73,rs30 where rs30.rs1=rs73.rs4 and rs73.rs1 = ? 
                and rs73.rs22 = ? and rs73.rs22 = ?
            ", [$noreg, $flagruangan, $mpl->rs1]);
            $subMpl = $subMplRow ? (float)($subMplRow[0]->subtotal ?? 0) : 0;
            $penunjangLainList[] = [
                'kode' => $mpl->rs1,
                'nama' => $mpl->rs2,
                'subtotal' => $subMpl
            ];
            $totalPenunjangLainDinamis += $subMpl;
        }

        $darahRow = DB::select("
            select sum(rs12+rs13) as subtotal from rs231 where rs1 = ? and rs14 = ? and rs14<>'POL014'
        ", [$noreg, $flagruangan]);
        $darahTotal = $darahRow ? (float)($darahRow[0]->subtotal ?? 0) : 0;

        $totalPenunjang = $laboratoriumTotal + $radiologiTotal + $operasiTotal + $hemodialisaTotal + $totalPenunjangLainDinamis + $darahTotal;

        // 11. Farmasi Ruangan
        $farmasiRow = DB::select("
            select round(sum(subtotalx),0) as subtotal from(
                select sum((rs38.rs6*rs38.rs8)+rs38.rs10) as subtotalx from rs38,rs32,rs9 where rs32.rs1=rs38.rs4 and rs38.rs1 = ? and rs38.lunas<>'1' 
                and rs9.rs1=rs38.rs21 and rs38.rs20 = ? and rs38.rs25='CENTRAL'
                union all
                select IF(rs39.rs8>1,sum((rs40.rs7*rs40.rs5)+rs39.rs8),sum((rs40.rs7*rs40.rs5))) as subtotalx 
                from rs39,rs40,rs32,rs9 where rs39.rs1=rs40.rs1 and rs32.rs1=rs40.rs4 and rs39.rs1 = ? and rs39.lunas<>'1' 
                and rs9.rs1=rs39.rs16 and rs39.rs15 = ? and rs39.rs19='CENTRAL'
                union all					
                select sum((rs62.rs6*rs62.rs8)+rs62.rs10) as subtotalx from rs62,rs32,rs9 where rs32.rs1=rs62.rs4 and rs62.rs1 = ? and rs62.lunas<>'1' 
                and rs9.rs1=rs62.rs21 and rs62.rs20 = ? and rs62.rs25='CENTRAL'
                union all
                select IF(rs63.rs8>1,sum((rs64.rs7*rs64.rs5)+rs63.rs8),sum((rs64.rs7*rs64.rs5))) as subtotalx 
                from rs63,rs64,rs32,rs9 where rs63.rs1=rs64.rs1 and rs32.rs1=rs64.rs4 and rs63.rs1 = ? and rs63.lunas<>'1' 
                and rs9.rs1=rs63.rs16 and rs63.rs15 = ? and rs63.rs19='CENTRAL'
            ) as vx
        ", [$noreg, $flagruangankelas, $noreg, $flagruangankelas, $noreg, $flagruangankelas, $noreg, $flagruangankelas]);
        $farmasiTotal = $farmasiRow ? (float)($farmasiRow[0]->subtotal ?? 0) : 0;

        // 12. IRD Ruangan
        $ird = 0;
        if ($flagruangan === 'POL014') {
            $sqlIrd = DB::select("select rs1 from rs17 where rs1 = ? and rs8 = 'POL014'", [$noreg]);
            if (!empty($sqlIrd)) {
                $ird += 8000;
            }
            $irdTindakan = DB::select("
                select sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal 
                from rs73,rs30,rs21 where rs30.rs1=rs73.rs4 and rs21.rs1=SUBSTRING_INDEX(rs73.rs8,';',1) 
                and rs73.rs1 = ? and rs73.rs22='POL014'
            ", [$noreg]);
            $ird += $irdTindakan ? (float)($irdTindakan[0]->subtotal ?? 0) : 0;
        }
        $irdTotal = $ird;

        $grandTotal = $administrasi
            + $akomodasiTotal
            + $tindakanDokterSubtotal
            + $visiteDokterSubtotal
            + $tindakanPerawatSubtotal
            + $asuhanGiziSubtotal
            + $makanPasienSubtotal
            + $oksigenSubtotal
            + $jasaKeperawatanSubtotal
            + $totalPenunjang
            + $farmasiTotal
            + $irdTotal;

        return [
            'header' => [
                'noreg' => $pasien->noreg,
                'norm' => $pasien->norm,
                'nama' => $pasien->nama,
                'pekerjaan' => $pasien->pekerjaan ?? '',
                'umur' => $umurStr,
                'alamat' => $pasien->alamat ?? '',
                'kelamin' => $pasien->kelamin,
                'ruangan' => $namaRuangan,
                'kelas' => $pasien->kelas,
                'ongkos_per_hari' => $tarifKamarPerHari,
                'tglmasuk' => $pasien->tglmasuk,
                'tglkeluar' => ($pasien->tglkeluar && $pasien->tglkeluar !== '0000-00-00 00:00:00') ? $pasien->tglkeluar : '0000-00-00 00:00:00',
                'dokter' => $pasien->dokter ?? '',
                'sistembayar' => ($flagsistembayar && $flagsistembayar !== 'ALL' && $namaSistemBayar) ? $namaSistemBayar : ($pasien->sistembayar ?: '-'),
            ],
            'rincian' => [
                'administrasi' => $administrasi,
                'akomodasi' => [
                    'total' => $akomodasiTotal,
                    'items' => $akomodasiList
                ],
                'materai' => 0,
                'tindakan_dokter' => $tindakanDokterSubtotal,
                'visite_dokter' => $visiteDokterSubtotal,
                'tindakan_perawat' => $tindakanPerawatSubtotal,
                'asuhan_gizi' => $asuhanGiziSubtotal,
                'makan_pasien' => $makanPasienSubtotal,
                'oksigen' => $oksigenSubtotal,
                'jasa_keperawatan' => $jasaKeperawatanSubtotal,
                'penunjang' => [
                    'total' => $totalPenunjang,
                    'laboratorium' => $laboratoriumTotal,
                    'radiologi' => $radiologiTotal,
                    'endoscope' => 0,
                    'operasi' => $operasiTotal,
                    'ruang_rr' => 0,
                    'fisioterapi' => 0,
                    'hemodialisa' => $hemodialisaTotal,
                    'cardio' => 0,
                    'eeg' => 0,
                    'psikologi' => 0,
                    'penggunaan_darah' => $darahTotal,
                    'jenasah' => 0,
                    'ambulan' => 0,
                    'apheresis' => 0,
                    'cathlab' => 0,
                    'penunjang_keluar' => 0,
                    'penunjang_lain' => $penunjangLainList,
                ],
                'farmasi' => $farmasiTotal,
                'operasi_cito' => 0,
                'farmasi_ird' => 0,
                'ird' => $irdTotal
            ],
            'grand_total' => $grandTotal
        ];
    }

public function getFakturDetail(Request $request)
    {
        $noreg = trim($request->noreg);
        if (!$noreg) {
            return new JsonResponse(['message' => 'Noreg tidak boleh kosong'], 422);
        }

        // Header Pasien
        $pasien = DB::table('rs23')
            ->join('rs15', 'rs15.rs1', '=', 'rs23.rs2')
            ->leftJoin('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->leftJoin('rs9', 'rs9.rs1', '=', 'rs23.rs19')
            ->leftJoin('rs21', 'rs21.rs1', '=', 'rs23.rs10')
            ->where('rs23.rs1', $noreg)
            ->select(
                'rs23.rs1 as noreg',
                'rs23.rs2 as norm',
                'rs15.rs2 as nama',
                'rs15.rs4 as alamat',
                'rs15.rs17 as kelamin',
                'rs15.rs16 as tgllahir',
                'rs15.rs33 as pekerjaan',
                'rs23.rs3 as tglmasuk',
                'rs23.rs4 as tglkeluar',
                'rs23.rs5 as koderuangankamar',
                'rs24.rs2 as ruang_poli',
                'rs24.rs3 as kelas',
                'rs24.rs4 as kd_ruangpoli',
                'rs23.rs19 as kodesistembayar',
                'rs9.rs2 as sistembayar',
                'rs23.rs10 as kodedokter',
                'rs21.rs2 as dokter'
            )
            ->first();

        if (!$pasien) {
            return new JsonResponse(['message' => 'Data pasien ranap tidak ditemukan'], 404);
        }

        $umurStr = '-';
        if ($pasien->tgllahir && $pasien->tgllahir !== '1900-01-01') {
            $tglLahirObj = new \DateTime($pasien->tgllahir);
            $tglMasukObj = new \DateTime($pasien->tglmasuk);
            $diff = $tglLahirObj->diff($tglMasukObj);
            if ($diff->y > 0) {
                $umurStr = $diff->y . ' thn';
            } elseif ($diff->m > 0) {
                $umurStr = $diff->m . ' bln';
            } else {
                $umurStr = $diff->d . ' hari';
            }
        }

        // 1. Administrasi - pakai rs35x K1# + rs30tarif A1# sesuai kelas
        $administrasi = 0;
        $rs35xAdm = DB::table('rs35x')
            ->where('rs1', $noreg)
            ->where('rs3', 'K1#')
            ->orderBy('rs4', 'desc')
            ->select('rs8 as kodesistembayar', 'rs17 as kelas')
            ->first();

        if ($rs35xAdm) {
            $admTarif = DB::table('rs30tarif')->where('rs3', 'A1#')->first();
            if ($admTarif) {
                switch ($rs35xAdm->kelas) {
                    case '3':
                        $administrasi = (float)$admTarif->rs6 + (float)$admTarif->rs7;
                        break;
                    case '2':
                        $administrasi = (float)$admTarif->rs8 + (float)$admTarif->rs9;
                        break;
                    case '1':
                    case 'IC':
                    case 'ICC':
                    case 'NICU':
                    case 'IN':
                        $administrasi = (float)$admTarif->rs10 + (float)$admTarif->rs11;
                        break;
                    case 'Utama':
                        $administrasi = (float)$admTarif->rs12 + (float)$admTarif->rs13;
                        break;
                    case 'VIP':
                        $administrasi = (float)$admTarif->rs14 + (float)$admTarif->rs15;
                        break;
                    case 'VVIP':
                        $administrasi = (float)$admTarif->rs16 + (float)$admTarif->rs17;
                        break;
                }
            }
        }

        // 2. Akomodasi / Kamar
        $akomodasiItems = DB::table('rs35x')
            ->join('rs24', 'rs24.rs1', '=', 'rs35x.rs18')
            ->where('rs35x.rs1', $noreg)
            ->select('rs35x.rs4 as tgl', 'rs35x.rs6 as keterangan', 'rs24.rs2 as ruang', DB::raw('(rs35x.rs7+rs35x.rs14) as harga'))
            ->get();
        $akomodasiTotal = (float)$akomodasiItems->sum('harga');

        // 3. Biaya Pembuatan Dokumen dan Materai
        $materai = (float)DB::table('rsjr')
            ->where('rs1', $noreg)
            ->where('rs7', '<>', 'IRD')
            ->sum('rs5');

        // 4. Jasa Pelayanan Dokter - filter rs21.rs13='1' + whitelist ruangan
        $ruanganWhitelist = ['BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','KA','ISHK','TR'];
        $tindakanDokterItems = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->leftJoin('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->where('rs21.rs13', '1')
            ->whereIn('rs73.rs22', $ruanganWhitelist)
            ->where('rs73.rs1', $noreg)
            ->select(
                'rs73.rs3 as tgl',
                'rs73.rs2 as nota',
                'rs30.rs2 as keterangan',
                'rs21.rs2 as dokter',
                DB::raw('(rs73.rs7+rs73.rs13) as biaya'),
                'rs73.rs5 as jml',
                DB::raw('((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal')
            )
            ->get();
        $tindakanDokterTotal = (float)$tindakanDokterItems->sum('subtotal');

        // 5. Biaya Visite, Konsul & Oncall
        $visiteItems = DB::table('rs140')
            ->leftJoin('rs21', 'rs21.rs1', '=', 'rs140.rs3')
            ->leftJoin('rs30tarif', 'rs30tarif.rs3', '=', 'rs140.rs6')
            ->where('rs140.rs1', $noreg)
            ->select(
                'rs140.id',
                DB::raw("concat(dayofmonth(rs140.rs2),'-',month(rs140.rs2),'-',year(rs140.rs2),' ',time(rs140.rs2)) as tgl"),
                'rs30tarif.rs2 as keterangan',
                'rs21.rs2 as dokter',
                DB::raw('round(rs140.rs4+rs140.rs5, 0) as biaya')
            )
            ->orderBy('rs140.id', 'asc')
            ->get();
        $visiteTotal = (float)$visiteItems->sum('biaya');

        // 6. Tindakan Keperawatan - filter rs21.rs13 IN ('2','3') + whitelist ruangan WKKB tambahan
        $ruanganWhitelistPerawat = ['BG','BR','DA','FA','IC','ICC','MA','ME','WK','WKUT','WKVVIP','WKKB','KA','ISHK','TR'];
        $tindakanPerawatRaw = DB::table('rs73')
            ->leftJoin('v_gudang', 'v_gudang.rs1', '=', 'rs73.rs22')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->leftJoin('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->whereIn('rs21.rs13', ['2', '3'])
            ->whereIn('rs73.rs22', $ruanganWhitelistPerawat)
            ->where('rs73.rs1', $noreg)
            ->select(
                'v_gudang.rs2 as ruang',
                'rs73.rs3 as tgl',
                'rs73.rs2 as nota',
                'rs30.rs2 as keterangan',
                'rs73.rs8 as kodepelaksana',
                DB::raw('(rs73.rs7+rs73.rs13) as biaya'),
                'rs73.rs5 as jml',
                DB::raw('((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal')
            )
            ->get();

        $tindakanPerawatItems = $tindakanPerawatRaw->map(function ($row) {
            $pelaksanaArr = array_filter(explode(';', $row->kodepelaksana));
            $pelaksanaNames = [];
            if (!empty($pelaksanaArr)) {
                $pelaksanaRows = DB::table('rs21')->whereIn('rs1', $pelaksanaArr)->pluck('rs2')->toArray();
                $pelaksanaNames = $pelaksanaRows;
            }
            $row->pelaksana = implode(', ', $pelaksanaNames);
            return $row;
        });
        $tindakanPerawatTotal = (float)$tindakanPerawatItems->sum('subtotal');

        // 7. Asuhan Gizi
        $asuhanGiziItems = DB::table('rs202')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs202.rs3')
            ->where('rs202.rs1', $noreg)
            ->where('rs30tarif.rs1', 'K00013')
            ->select('rs202.rs2 as tgl', 'rs30tarif.rs2 as keterangan', DB::raw('(rs202.rs4+rs202.rs5) as biaya'), 'rs202.rs7 as waktu')
            ->get();
        $asuhanGiziTotal = (float)$asuhanGiziItems->sum('biaya');

        // 8. Makan Pasien
        $makanPasienItems = DB::table('rs202')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs202.rs3')
            ->where('rs202.rs1', $noreg)
            ->whereIn('rs30tarif.rs1', ['K00004', 'K00003'])
            ->select('rs202.rs2 as tgl', 'rs30tarif.rs2 as keterangan', DB::raw('(rs202.rs4+rs202.rs5) as biaya'), 'rs202.rs7 as waktu')
            ->get();
        $makanPasienTotal = (float)$makanPasienItems->sum('biaya');

        // 9. Biaya Oksigen
        $oksigenItems = DB::table('rs205')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs205.rs3')
            ->where('rs205.rs1', $noreg)
            ->select('rs205.id', 'rs205.rs2 as tgl', 'rs30tarif.rs2 as keterangan', DB::raw('((rs205.rs4+rs205.rs5)*rs205.rs6) as biaya'))
            ->orderBy('rs205.id', 'asc')
            ->get();
        $oksigenTotal = (float)$oksigenItems->sum('biaya');

        // 10. Jasa Keperawatan
        $keperawatanItems = DB::table('rs203')
            ->leftJoin('v_gudang', 'v_gudang.rs1', '=', 'rs203.rs8')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs203.rs3')
            ->where('rs203.rs1', $noreg)
            ->select('v_gudang.rs2 as ruang', 'rs203.id', 'rs203.rs2 as tgl', 'rs30tarif.rs2 as keterangan', DB::raw('round(rs203.rs4+rs203.rs5,0) as biaya'))
            ->orderBy('rs203.id', 'asc')
            ->get();
        $keperawatanTotal = (float)$keperawatanItems->sum('biaya');

        // 11. Laboratorium - UNION: rs49+rs51 whitelist ruangan + rs73 khusus LAB
        $labPemeriksaanSql = "
            SELECT '' as flag, rs51.rs2 as nota, rs51.rs3 as tgl, rs51.rs4 as kode, rs51.rs8 as kodedokter,
                   rs49.rs2 as keterangan, (rs51.rs6+rs51.rs13) as biaya, rs51.rs5 as jml,
                   ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotal
            FROM rs49, rs51
            WHERE rs49.rs1=rs51.rs4 AND rs49.rs21='' AND rs51.rs18<>'' AND rs51.rs1 = ?
            AND (rs51.rs23='BG' OR rs51.rs23='BR' OR rs51.rs23='DA' OR rs51.rs23='FA' OR rs51.rs23='IC'
              OR rs51.rs23='ICC' OR rs51.rs23='MA' OR rs51.rs23='ME' OR rs51.rs23='WK' OR rs51.rs23='WKUT'
              OR rs51.rs23='WKVVIP' OR rs51.rs23='WKKB' OR rs51.rs23='KA' OR rs51.rs23='ISHK' OR rs51.rs23='TR')
            UNION ALL
            SELECT 'x' as flag, rs51.rs2 as nota, rs51.rs3 as tgl, rs51.rs4 as kode, rs51.rs8 as kodedokter,
                   rs49.rs21 as keterangan, (rs51.rs6+rs51.rs13) as biaya, rs51.rs5 as jml,
                   ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotal
            FROM rs49, rs51
            WHERE rs49.rs1=rs51.rs4 AND rs49.rs21<>'' AND rs51.rs18<>'' AND rs51.rs1 = ?
            AND (rs51.rs23='BG' OR rs51.rs23='BR' OR rs51.rs23='DA' OR rs51.rs23='FA' OR rs51.rs23='IC'
              OR rs51.rs23='ICC' OR rs51.rs23='MA' OR rs51.rs23='ME' OR rs51.rs23='WK' OR rs51.rs23='WKUT'
              OR rs51.rs23='WKVVIP' OR rs51.rs23='WKKB' OR rs51.rs23='KA' OR rs51.rs23='ISHK' OR rs51.rs23='TR')
            GROUP BY rs51.rs2, rs49.rs21
        ";
        $labItems = DB::select($labPemeriksaanSql, [$noreg, $noreg]);

        // Laboratorium tambahan dari rs73 - tindakan khusus LAB
        $labRs73Items = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->leftJoin('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->where('rs73.rs1', $noreg)
            ->whereIn('rs73.rs4', ['T00086','T00176','T00299','T00337','T00386','T00387'])
            ->where('rs73.rs22', 'LAB')
            ->select(
                'rs73.rs3 as tgl',
                'rs73.rs2 as nota',
                'rs30.rs2 as keterangan',
                'rs21.rs2 as dokter',
                DB::raw('((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal')
            )
            ->get();

        $labTotal = collect($labItems)->sum('subtotal') + $labRs73Items->sum('subtotal');

        // 12. Radiologi
        $radiologiItems = DB::table('rs48')
            ->join('rs47', 'rs47.rs1', '=', 'rs48.rs4')
            ->where('rs48.rs1', $noreg)
            ->select(
                'rs48.rs3 as tgl', 'rs48.rs2 as nota',
                DB::raw("CONCAT(rs47.rs2, ' (', rs47.rs3, ')') as keterangan"),
                'rs48.rs23 as ukuran',
                DB::raw("'Radiologi' as jenis"),
                DB::raw('round(rs48.rs6+rs48.rs8, 0) as biaya'),
                'rs48.rs24 as jml',
                DB::raw('((rs48.rs6+rs48.rs8)*rs48.rs24) as subtotal')
            )
            ->orderBy('rs48.rs3', 'asc')
            ->get();
        $radiologiTotal = (float)$radiologiItems->sum('subtotal');

        // 13. Endoscope - UNION rs246 + rs73 POL031
        $endoscopyTotal = (float)DB::select("
            SELECT sum(subtotal) as subtotal FROM (
                SELECT sum(rs5) as subtotal FROM rs246 WHERE rs1 = ?
                UNION ALL
                SELECT sum((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
                FROM rs73, rs30 WHERE rs30.rs1=rs73.rs4 AND rs73.rs1 = ? AND rs73.rs22='POL031'
            ) as vendoscope
        ", [$noreg, $noreg])[0]->subtotal ?? 0;
        $endoscopyItems = [];

        // 14. Kamar Operasi / IBS
        $operasiItems = DB::select("
            SELECT nota, tgl, jenis, keterangan, subtotal FROM (
                SELECT rs54.rs2 as nota, rs54.rs3 as tgl, rs53.rs4 as jenis, rs53.rs2 as keterangan,
                       ((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotal
                FROM rs54, rs53 WHERE rs53.rs1=rs54.rs4 AND rs54.rs1 = ?
                UNION ALL
                SELECT rs226.rs2 as nota, rs226.rs3 as tgl, rs53.rs4 as jenis, rs53.rs2 as keterangan,
                       ((rs226.rs5+rs226.rs6+rs226.rs7)*rs226.rs8) as subtotal
                FROM rs226, rs53 WHERE rs53.rs1=rs226.rs4 AND rs226.rs1 = ?
            ) as v_operasi
        ", [$noreg, $noreg]);
        $operasiTotal = collect($operasiItems)->sum('subtotal');

        // 15. Ruang RR
        $ruangRRItems = DB::select("
            SELECT rs73.rs2 as nota, rs73.rs3 as tgl, rs73.rs5 as jumlah, rs30.rs2 as keterangan,
                   ((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal
            FROM rs73, rs30 WHERE rs30.rs1=rs73.rs4 AND rs73.rs1 = ?
            AND (rs73.rs22='OPERASI' OR rs73.rs22='OPERASIIRD')
        ", [$noreg]);
        $ruangRRTotal = collect($ruangRRItems)->sum('subtotal');

        // 16. Fisioterapi - logika IF: (rs73.rs25<>'POL014' AND rs73.rs25<>'') OR rs73.rs25=''
        $fisioterapiItems = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', 'FISIO')
            ->select(
                'rs73.rs3 as tgl', 'rs73.rs2 as nota', 'rs30.rs2 as keterangan',
                DB::raw("if((rs73.rs25<>'POL014' and rs73.rs25<>'') or rs73.rs25='', ((rs73.rs7+rs73.rs13)*rs73.rs5), 0) as subtotal")
            )
            ->get();
        $fisioterapiTotal = (float)$fisioterapiItems->sum('subtotal');

        // 17. Hemodialisa - filter rs21.rs13 IN ('2','3') + rs22='PEN005'
        $hemodialisaRaw = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->leftJoin('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->whereIn('rs21.rs13', ['2', '3'])
            ->where('rs73.rs22', 'PEN005')
            ->where('rs73.rs1', $noreg)
            ->select(
                'rs73.rs3 as tgl',
                'rs73.rs2 as nota',
                'rs30.rs2 as keterangan',
                'rs73.rs8 as kodepelaksana',
                DB::raw('(rs73.rs7+rs73.rs13) as biaya'),
                'rs73.rs5 as jml',
                DB::raw('((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal')
            )
            ->get();

        $hemodialisaItems = $hemodialisaRaw->map(function ($row) {
            $pelaksanaArr = array_filter(explode(';', $row->kodepelaksana));
            $pelaksanaNames = [];
            if (!empty($pelaksanaArr)) {
                $pelaksanaNames = DB::table('rs21')->whereIn('rs1', $pelaksanaArr)->pluck('rs2')->toArray();
            }
            $row->pelaksana = implode(', ', $pelaksanaNames);
            return $row;
        });
        $hemodialisaTotal = (float)$hemodialisaItems->sum('subtotal');

        // 18. Anestesi Di Luar OK - dari rs19.penunjang_lain='1' dengan filter IF POL014
        $penunjangLainKodes = DB::table('rs19')->where('penunjang_lain', '1')->pluck('rs1')->toArray();
        $anestesiTotal = 0;
        $anestesiItems = [];
        if (!empty($penunjangLainKodes)) {
            $anestesiRows = DB::table('rs73')
                ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                ->where('rs73.rs1', $noreg)
                ->whereIn('rs73.rs22', $penunjangLainKodes)
                ->select(
                    'rs73.rs3 as tgl', 'rs73.rs2 as nota', 'rs30.rs2 as keterangan',
                    DB::raw("if((rs73.rs25<>'POL014' and rs73.rs25<>'') or rs73.rs25='', ((rs73.rs7+rs73.rs13)*rs73.rs5), 0) as subtotal")
                )
                ->get();
            $anestesiTotal = (float)$anestesiRows->sum('subtotal');
            $anestesiItems = $anestesiRows;
        }

        // 19. Cardio - filter rs21.rs13 IN ('2','3') + rs22='POL026'
        $cardioRaw = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->leftJoin('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->whereIn('rs21.rs13', ['2', '3'])
            ->where('rs73.rs22', 'POL026')
            ->where('rs73.rs1', $noreg)
            ->select(
                'rs73.rs3 as tgl', 'rs73.rs2 as nota', 'rs30.rs2 as keterangan', 'rs73.rs8 as kodepelaksana',
                DB::raw('(rs73.rs7+rs73.rs13) as biaya'),
                'rs73.rs5 as jml',
                DB::raw('((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal')
            )
            ->get();
        $cardioItems = $cardioRaw->map(function ($row) {
            $pelaksanaArr = array_filter(explode(';', $row->kodepelaksana));
            $row->pelaksana = !empty($pelaksanaArr)
                ? implode(', ', DB::table('rs21')->whereIn('rs1', $pelaksanaArr)->pluck('rs2')->toArray())
                : '';
            return $row;
        });
        $cardioTotal = (float)$cardioItems->sum('subtotal');

        // 20. EEG - filter rs21.rs13 IN ('2','3') + rs22='POL024'
        $eegRaw = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->leftJoin('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->whereIn('rs21.rs13', ['2', '3'])
            ->where('rs73.rs22', 'POL024')
            ->where('rs73.rs1', $noreg)
            ->select(
                'rs73.rs3 as tgl', 'rs73.rs2 as nota', 'rs73.rs8 as kodepelaksana',
                DB::raw('((rs73.rs7+rs73.rs13)*rs73.rs5) as subtotal')
            )
            ->get();
        $eegItems = $eegRaw->map(function ($row) {
            $pelaksanaArr = array_filter(explode(';', $row->kodepelaksana));
            $row->pelaksana = !empty($pelaksanaArr)
                ? implode(', ', DB::table('rs21')->whereIn('rs1', $pelaksanaArr)->pluck('rs2')->toArray())
                : '';
            return $row;
        });
        $eegTotal = (float)$eegItems->sum('subtotal');

        // 21. Psikologi
        $psikologiTotal = (float)DB::table('psikologi_trans')
            ->join('rs30', 'rs30.rs1', '=', 'psikologi_trans.rs4')
            ->where('psikologi_trans.rs1', $noreg)
            ->sum(DB::raw('(psikologi_trans.rs7+psikologi_trans.rs13)*psikologi_trans.rs5'));

        // 22. Bank Darah - rs231 filter rs14<>'POL014'
        $bankDarahItems = DB::table('rs231')
            ->where('rs1', $noreg)
            ->where('rs14', '<>', 'POL014')
            ->select('rs3 as nota', 'rs4 as tgl', 'rs5 as nokantong', 'rs6 as jenis', 'rs7 as golda', 'rs11 as hasil', DB::raw('(rs12+rs13) as subtotal'))
            ->get();
        $bankDarahTotal = (float)$bankDarahItems->sum('subtotal');

        // 23. Jenazah - rs275 UNION rs273 (keduanya filter POL014)
        $jenazahTotal = (float)DB::select("
            SELECT sum(subtotal) as subtotal FROM (
                SELECT rs275.rs3 as keterangan, sum(rs275.rs5+rs275.rs6) as subtotal
                FROM rs275 WHERE rs275.rs1 = ? AND rs275.rs7<>'POL014'
                UNION ALL
                SELECT rs30.rs2 as keterangan, sum(rs273.rs6+rs273.rs7) as subtotal
                FROM rs273, rs30 WHERE rs273.rs5=rs30.rs1 AND rs273.rs1 = ? AND rs273.rs14<>'POL014'
            ) as vx
        ", [$noreg, $noreg])[0]->subtotal ?? 0;

        // 24. Ambulan
        $ambulanItems = DB::select("
            SELECT rs283.id, rs283.rs1 AS noreg, rs283.rs4 AS tanggal, rs281.rs2 AS tujuan,
                   rs283.rs6 AS keterangan, rs283.rs7 AS jarak, rs283.rs8 AS jpambulan,
                   (rs283.rs2 + rs283.rs15 + rs283.rs16 + rs283.rs17 + rs283.rs18 + rs283.rs23 + rs283.rs26) AS subtotal
            FROM rs283, ambulan, rs281
            WHERE rs283.rs5 = rs281.rs1 AND rs283.rs9 = ambulan.rs1 AND rs283.rs1 = ?
            GROUP BY rs283.rs1
        ", [$noreg]);
        $ambulanTotal = collect($ambulanItems)->sum('subtotal');

        // 25. Apheresis - UNION tapheresis + trans_darahApheresis
        $apheresisRow = DB::select("
            SELECT sum(subtotalx) as subtotal FROM (
                SELECT round(sum(tapheresis.js+tapheresis.jp)) as subtotalx
                FROM tpermintaanapheresis, tapheresis
                WHERE tpermintaanapheresis.noreg=tapheresis.noreg AND tpermintaanapheresis.flag=1
                  AND tpermintaanapheresis.noreg = ?
                GROUP BY tpermintaanapheresis.nota_permintaan
                UNION ALL
                SELECT round(sum(trans_darahApheresis.rs10+trans_darahApheresis.rs11)) as subtotalx
                FROM tpermintaanapheresis, trans_darahApheresis
                WHERE tpermintaanapheresis.noreg=trans_darahApheresis.rs1 AND tpermintaanapheresis.flag=1
                  AND tpermintaanapheresis.noreg = ?
                GROUP BY tpermintaanapheresis.nota_permintaan
            ) as wew
        ", [$noreg, $noreg]);
        $apheresisTotal = (float)($apheresisRow[0]->subtotal ?? 0);

        // 26. Penunjang Keluar
        $penunjangKeluarTotal = (float)DB::table('lab_keluar')
            ->where('noreg', $noreg)
            ->where('ruangan', '<>', 'POL014')
            ->sum(DB::raw('(harga_sarana + harga_pelayanan) * jumlah'));

        // 27. Biaya Farmasi / Obat - 4 tabel UNION, hitung (harga*jml)+embalage
        $farmasiSql = "
            SELECT rs38.rs3 as tgl, rs38.rs2 as nota, rs32.rs2 as obat,
                   rs38.rs6 as harga, rs38.rs8 as jml, rs38.rs10 as embalage
            FROM rs38, rs32 WHERE rs32.rs1=rs38.rs4 AND rs38.rs1 = ? AND rs38.lunas <> '1'
            UNION ALL
            SELECT rs39.rs3 as tgl, rs39.rs2 as nota, rs32.rs2 as obat,
                   rs40.rs7 as harga, rs40.rs5 as jml, IF(rs39.rs8>1, rs39.rs8, 0) as embalage
            FROM rs39, rs40, rs32 WHERE rs39.rs1=rs40.rs1 AND rs32.rs1=rs40.rs4 AND rs39.rs1 = ? AND rs39.lunas <> '1'
            UNION ALL
            SELECT rs62.rs3 as tgl, rs62.rs2 as nota, rs32.rs2 as obat,
                   rs62.rs6 as harga, rs62.rs8 as jml, rs62.rs10 as embalage
            FROM rs62, rs32 WHERE rs32.rs1=rs62.rs4 AND rs62.rs1 = ? AND rs62.lunas <> '1'
            UNION ALL
            SELECT rs63.rs3 as tgl, rs63.rs2 as nota, rs32.rs2 as obat,
                   rs64.rs7 as harga, rs64.rs5 as jml, IF(rs63.rs8>1, rs63.rs8, 0) as embalage
            FROM rs63, rs64, rs32 WHERE rs63.rs1=rs64.rs1 AND rs32.rs1=rs64.rs4 AND rs63.rs1 = ? AND rs63.lunas <> '1'
        ";
        $farmasiRaw = DB::select($farmasiSql, [$noreg, $noreg, $noreg, $noreg]);
        $farmasiItems = collect($farmasiRaw)->map(function ($row) {
            $row->subtotal = ($row->harga * $row->jml) + $row->embalage;
            return $row;
        });
        $farmasiTotal = (float)$farmasiItems->sum('subtotal');

        // 28-30. E-Resep (dari database farmasi)
        $eResepNonRacikanItems = collect();
        $eResepNonRacikanTotal = 0;
        $eResepRacikanItems = collect();
        $eResepRacikanTotal = 0;
        $eResepRRacikanTotal = 0;

        try {
            $eResepNonRacikanItems = DB::connection('farmasi')->table('resep_keluar_h')
                ->leftJoin('resep_keluar_r', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                ->leftJoin('new_masterobat', 'resep_keluar_r.kdobat', '=', 'new_masterobat.kd_obat')
                ->where('resep_keluar_h.noreg', $noreg)
                ->select(
                    'resep_keluar_h.noresep',
                    'resep_keluar_h.tgl',
                    'new_masterobat.nama_obat as obat',
                    'resep_keluar_r.aturan',
                    'resep_keluar_r.konsumsi',
                    'resep_keluar_r.keterangan',
                    'resep_keluar_r.jumlah',
                    'resep_keluar_r.harga_jual as harga',
                    'resep_keluar_r.nilai_r as nilair',
                    DB::raw('round((resep_keluar_r.harga_jual * resep_keluar_r.jumlah) + resep_keluar_r.nilai_r) as subtotal')
                )
                ->get();
            $eResepNonRacikanTotal = (float)$eResepNonRacikanItems->sum('subtotal');

            $eResepRacikanItems = DB::connection('farmasi')->table('resep_keluar_h')
                ->leftJoin('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                ->leftJoin('new_masterobat', 'resep_keluar_racikan_r.kdobat', '=', 'new_masterobat.kd_obat')
                ->where('resep_keluar_h.noreg', $noreg)
                ->whereIn('resep_keluar_h.depo', ['Gd-04010102', 'Gd-04010103'])
                ->where('resep_keluar_racikan_r.kdobat', '<>', '')
                ->select(
                    'resep_keluar_h.noresep',
                    'resep_keluar_h.tgl',
                    'new_masterobat.nama_obat as obat',
                    'resep_keluar_racikan_r.jumlahdibutuhkan as konsumsi',
                    'resep_keluar_racikan_r.jumlah',
                    'resep_keluar_racikan_r.harga_jual as harga',
                    DB::raw('round(resep_keluar_racikan_r.harga_jual * resep_keluar_racikan_r.jumlah) as subtotal')
                )
                ->get();
            $eResepRacikanTotal = (float)$eResepRacikanItems->sum('subtotal');

            $eResepRRacikanTotal = (float)DB::connection('farmasi')->table('resep_keluar_h')
                ->leftJoin('resep_keluar_racikan_r', 'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
                ->where('resep_keluar_h.noreg', $noreg)
                ->whereIn('resep_keluar_h.depo', ['Gd-04010102', 'Gd-04010103'])
                ->sum('resep_keluar_racikan_r.nilai_r');
        } catch (\Exception $e) {
            // koneksi farmasi opsional
        }

        // Grand Total - sesuai formula legacy PHP baris 1535-1536:
        // $totalall = $akomodasi+$materai+$tindakandokter+$visite+$tindakanperawat+$asuhangizi
        //   +$makanpasien+$oksigen1+$keperawatan1+$laboratorium+$radiologi+$endoscopy+$operasi
        //   +$operasi2+$fisioterap+$hemo+$penunjangLain+$totalb+$psikologi+$darah+$jenasah
        //   +$total(ambulan)+$apheresis+$penunjangkeluar+$totalf+$totalnewnonracik+$totalracikan
        // Grand Total sesuai formula legacy fakturdetail.php baris 1535-1536:
        // $totalall = $akomodasi+$materai+$tindakandokter+$visite+$tindakanperawat+$asuhangizi
        //   +$makanpasien+$oksigen1+$keperawatan1+$laboratorium+$radiologi+$endoscopy+$operasi
        //   +$operasi2+$fisioterap+$hemo+$penunjangLain+$totalb+$psikologi+$darah+$jenasah
        //   +$total(ambulan)+$apheresis+$penunjangkeluar+$totalf+$totalnewnonracik+$totalracikan
        // CATATAN: $administrasi & $eResepRRacikanTotal ($totalr) TIDAK masuk ke totalall legacy!
        $grandTotal = $akomodasiTotal
            + $materai
            + $tindakanDokterTotal
            + $visiteTotal
            + $tindakanPerawatTotal
            + $asuhanGiziTotal
            + $makanPasienTotal
            + $oksigenTotal
            + $keperawatanTotal
            + $labTotal
            + $radiologiTotal
            + (float)$endoscopyTotal
            + $operasiTotal
            + $ruangRRTotal
            + $fisioterapiTotal
            + $hemodialisaTotal
            + $anestesiTotal
            + $cardioTotal
            + $eegTotal
            + $psikologiTotal
            + $bankDarahTotal
            + (float)$jenazahTotal
            + $ambulanTotal
            + $apheresisTotal
            + $penunjangKeluarTotal
            + $farmasiTotal
            + $eResepNonRacikanTotal
            + $eResepRacikanTotal;

        $response = [
            'header' => [
                'noreg'         => $pasien->noreg,
                'norm'          => $pasien->norm,
                'nama'          => $pasien->nama,
                'pekerjaan'     => $pasien->pekerjaan ?? '',
                'umur'          => $umurStr,
                'alamat'        => $pasien->alamat ?? '',
                'ruangan'       => $pasien->ruang_poli,
                'kelas'         => $pasien->kelas,
                'tglmasuk'      => $pasien->tglmasuk,
                'tglkeluar'     => $pasien->tglkeluar ?? '0000-00-00 00:00:00',
                'dokter'        => $pasien->dokter ?? '',
                'dipindahkan'   => '-',
                'sistembayar'   => $pasien->sistembayar ?? ''
            ],
            'details' => [
                'administrasi'      => ['title' => 'Administrasi',                        'total' => $administrasi,          'items' => []],
                'akomodasi'         => ['title' => 'Akomodasi / Kamar',                   'total' => $akomodasiTotal,         'items' => $akomodasiItems],
                'materai'           => ['title' => 'Biaya Pembuatan Dokumen dan Materai',  'total' => $materai,               'items' => []],
                'tindakan_dokter'   => ['title' => 'Jasa Pelayanan Dokter Umum/Spesialis', 'total' => $tindakanDokterTotal,   'items' => $tindakanDokterItems],
                'visite_dokter'     => ['title' => 'Biaya Visite, Konsul & Oncall',        'total' => $visiteTotal,           'items' => $visiteItems],
                'tindakan_perawat'  => ['title' => 'Tindakan Keperawatan',                 'total' => $tindakanPerawatTotal,  'items' => $tindakanPerawatItems],
                'asuhan_gizi'       => ['title' => 'Asuhan Gizi',                          'total' => $asuhanGiziTotal,       'items' => $asuhanGiziItems],
                'makan_pasien'      => ['title' => 'Makan Pasien',                         'total' => $makanPasienTotal,      'items' => $makanPasienItems],
                'oksigen'           => ['title' => 'Biaya Oksigen',                        'total' => $oksigenTotal,          'items' => $oksigenItems],
                'jasa_keperawatan'  => ['title' => 'Jasa Keperawatan',                     'total' => $keperawatanTotal,      'items' => $keperawatanItems],
                'laboratorium'      => ['title' => 'Laboratorium',                         'total' => $labTotal,              'items' => array_merge($labItems, $labRs73Items->toArray())],
                'radiologi'         => ['title' => 'Radiologi',                            'total' => $radiologiTotal,        'items' => $radiologiItems],
                'endoscopy'         => ['title' => 'Endoscope',                            'total' => $endoscopyTotal,        'items' => []],
                'kamar_operasi'     => ['title' => 'Kamar Operasi',                        'total' => $operasiTotal,          'items' => $operasiItems],
                'ruang_rr'          => ['title' => 'Ruang RR',                             'total' => $ruangRRTotal,          'items' => $ruangRRItems],
                'fisioterapi'       => ['title' => 'Fisioterapi',                          'total' => $fisioterapiTotal,      'items' => $fisioterapiItems],
                'hemodialisa'       => ['title' => 'Hemodialisa',                          'total' => $hemodialisaTotal,      'items' => $hemodialisaItems],
                'anestesi_luar_ok'  => ['title' => 'Anastesi Diluar Ok',                  'total' => $anestesiTotal,         'items' => $anestesiItems],
                'cardio'            => ['title' => 'Cardio',                               'total' => $cardioTotal,           'items' => $cardioItems],
                'eeg'               => ['title' => 'EEG',                                  'total' => $eegTotal,              'items' => $eegItems],
                'psikologi'         => ['title' => 'Psikologi',                            'total' => $psikologiTotal,        'items' => []],
                'bank_darah'        => ['title' => 'Bank Darah',                           'total' => $bankDarahTotal,        'items' => $bankDarahItems],
                'jenazah'           => ['title' => 'Jenazah',                              'total' => $jenazahTotal,          'items' => []],
                'ambulan'           => ['title' => 'Ambulan',                              'total' => $ambulanTotal,          'items' => $ambulanItems],
                'apheresis'         => ['title' => 'Apheresis',                            'total' => $apheresisTotal,        'items' => []],
                'penunjang_keluar'  => ['title' => 'Penunjang Keluar',                     'total' => $penunjangKeluarTotal,  'items' => []],
                'farmasi'           => ['title' => 'Biaya Farmasi / Obat',                 'total' => $farmasiTotal,          'items' => $farmasiItems],
                'eresep_non_racikan'=> ['title' => 'Biaya E-Resep (Non Racikan)',          'total' => $eResepNonRacikanTotal, 'items' => $eResepNonRacikanItems],
                'eresep_racikan'    => ['title' => 'Biaya E-Resep (Racikan)',              'total' => $eResepRacikanTotal,    'items' => $eResepRacikanItems],
                'eresep_r_racikan'  => ['title' => 'Biaya E-Resep (R Racikan)',            'total' => $eResepRRacikanTotal,   'items' => []],
            ],
            'grand_total' => $grandTotal
        ];

        return new JsonResponse(['result' => $response], 200);
    }

}

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

        $flagruangan = $request->flagruangan ? trim($request->flagruangan) : null;
        $flagsistembayar = $request->flagsistembayar ? trim($request->flagsistembayar) : null;
        $flagruangankelas = $request->flagruangankelas ? trim($request->flagruangankelas) : null;

        $sistembayarNama = $pasien->sistembayar;
        if ($flagsistembayar && $flagsistembayar !== $pasien->kodesistembayar) {
            $sistembayarRow = DB::table('rs9')->where('rs1', $flagsistembayar)->first();
            if ($sistembayarRow) {
                $sistembayarNama = $sistembayarRow->rs2;
            }
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

        $listRuangan = DB::table('rs35x')
            ->join('rs24', 'rs24.rs4', '=', 'rs35x.rs16')
            ->where('rs35x.rs1', $noreg)
            ->whereNotNull('rs35x.rs16')
            ->where('rs35x.rs16', '!=', '')
            ->select('rs35x.rs16 as kd_ruangan', 'rs24.rs2 as nama_ruangan')
            ->groupBy('rs35x.rs16', 'rs24.rs2')
            ->get()
            ->unique('kd_ruangan')
            ->values();

        $listSistemBayar = DB::table('rs35x')
            ->join('rs9', 'rs9.rs1', '=', 'rs35x.rs8')
            ->where('rs35x.rs1', $noreg)
            ->whereNotNull('rs35x.rs8')
            ->where('rs35x.rs8', '!=', '')
            ->select('rs35x.rs8 as kd_sistembayar', 'rs9.rs2 as nama_sistembayar')
            ->groupBy('rs35x.rs8', 'rs9.rs2')
            ->get()
            ->unique('kd_sistembayar')
            ->values();

        // 1. Administrasi
        $administrasi = 0;
        $rs35xFirst = DB::table('rs35x')
            ->where('rs1', $noreg)
            ->where('rs3', 'K1#')
            ->orderBy('rs4', 'asc')
            ->select('rs8 as kodesistembayar', 'rs17 as kelas')
            ->first();

        if ($rs35xFirst && $flagruangan && $rs35xFirst->kodesistembayar == $flagruangan) {
            $tarifA1 = DB::table('rs30tarif')->where('rs3', 'A1#')->first();
            if ($tarifA1) {
                switch ($rs35xFirst->kelas) {
                    case '3':
                        $administrasi = (float)$tarifA1->rs6 + (float)$tarifA1->rs7;
                        break;
                    case '2':
                        $administrasi = (float)$tarifA1->rs8 + (float)$tarifA1->rs9;
                        break;
                    case '1':
                    case 'IC':
                    case 'ICC':
                    case 'NICU':
                    case 'IN':
                        $administrasi = (float)$tarifA1->rs10 + (float)$tarifA1->rs11;
                        break;
                    case 'Utama':
                        $administrasi = (float)$tarifA1->rs12 + (float)$tarifA1->rs13;
                        break;
                    case 'VIP':
                        $administrasi = (float)$tarifA1->rs14 + (float)$tarifA1->rs15;
                        break;
                    case 'VVIP':
                        $administrasi = (float)$tarifA1->rs16 + (float)$tarifA1->rs17;
                        break;
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
                    WHEN 'Utama' THEN 'Utama'          
                    WHEN 'VIP' THEN 'VIP'      
                    WHEN 'VVIP' THEN 'VVIP' 
                    ELSE rs17
                END as kelas")
            )
            ->where('rs3', 'k1#')
            ->where('rs1', $noreg);

        if ($flagruangan) {
            $akomodasiQuery->where('rs16', $flagruangan);
        }
        if ($flagsistembayar) {
            $akomodasiQuery->where('rs8', $flagsistembayar);
        }

        $akomodasiList = $akomodasiQuery->groupBy('rs17', 'rs7', 'rs14')->get();
        $akomodasiTotal = (float)$akomodasiList->sum('subtotal');

        // 3. Jasa Dokter
        $tindakanDokterQuery = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->join('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->where('rs21.rs13', '1')
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', '<>', 'POL014');

        if ($flagruangan) {
            $tindakanDokterQuery->where('rs73.rs22', $flagruangan);
        }
        $tindakanDokterSubtotal = (float)$tindakanDokterQuery->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));

        // 4. Visite
        $visiteQuery = DB::table('rs140')
            ->join('rs21', 'rs21.rs1', '=', 'rs140.rs3')
            ->join('rs30tarif', 'rs30tarif.rs3', '=', 'rs140.rs6')
            ->where('rs140.rs1', $noreg);

        if ($flagruangan) {
            $visiteQuery->where('rs140.rs8', $flagruangan);
        }
        $visiteDokterSubtotal = (float)$visiteQuery->sum(DB::raw('rs140.rs4 + rs140.rs5'));

        // 5. Tindakan Perawat
        $tindakanPerawatQuery = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->join('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->whereIn('rs21.rs13', ['2', '3'])
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', '<>', 'POL014');

        if ($flagruangan) {
            $tindakanPerawatQuery->where('rs73.rs22', $flagruangan);
        }
        $tindakanPerawatSubtotal = (float)$tindakanPerawatQuery->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));

        // 6. Asuhan Gizi
        $giziQuery = DB::table('rs202')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs202.rs3')
            ->where('rs202.rs1', $noreg)
            ->where('rs30tarif.rs1', 'K00013');

        if ($flagruangan) {
            $giziQuery->where('rs202.rs8', $flagruangan);
        }
        $asuhanGiziSubtotal = (float)$giziQuery->sum(DB::raw('rs202.rs4 + rs202.rs5'));

        // 7. Makan Pasien
        $makanQuery = DB::table('rs202')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs202.rs3')
            ->where('rs202.rs1', $noreg)
            ->whereIn('rs30tarif.rs1', ['K00004', 'K00003']);

        if ($flagruangan) {
            $makanQuery->where('rs202.rs8', $flagruangan);
        }
        $makanPasienSubtotal = (float)$makanQuery->sum(DB::raw('rs202.rs4 + rs202.rs5'));

        // 8. Oksigen
        $oksigenQuery = DB::table('rs205')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs205.rs3')
            ->where('rs205.rs1', $noreg);

        if ($flagruangan) {
            $oksigenQuery->where('rs205.rs8', $flagruangan);
        }
        $oksigenSubtotal = (float)$oksigenQuery->sum(DB::raw('(rs205.rs4 + rs205.rs5) * rs205.rs6'));

        // 9. Jasa Keperawatan
        $jasaKepQuery = DB::table('rs203')
            ->join('rs30tarif', 'rs30tarif.rs1', '=', 'rs203.rs3')
            ->where('rs203.rs1', $noreg);

        if ($flagruangan) {
            $jasaKepQuery->where('rs203.rs8', $flagruangan);
        }
        $jasaKeperawatanSubtotal = (float)$jasaKepQuery->sum(DB::raw('rs203.rs4 + rs203.rs5'));

        // 10. Penunjang
        $labPemeriksaanQuery = DB::table('rs51')
            ->join('rs49', 'rs49.rs1', '=', 'rs51.rs4')
            ->where('rs51.rs1', $noreg)
            ->where('rs51.lunas', '<>', '1')
            ->where('rs51.rs23', '<>', 'POL014')
            ->where('rs51.rs21', '<>', '');

        if ($flagruangan) {
            $labPemeriksaanQuery->where('rs51.rs23', $flagruangan);
        }
        $lab1 = (float)$labPemeriksaanQuery->sum(DB::raw('(rs51.rs6 + rs51.rs13) * rs51.rs5'));

        $lab2Query = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', 'LAB');
        if ($flagruangan) {
            $lab2Query->where('rs73.rs22', $flagruangan);
        }
        $lab2 = (float)$lab2Query->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));
        $laboratoriumTotal = $lab1 + $lab2;

        $radQuery = DB::table('rs48')
            ->join('rs47', 'rs47.rs1', '=', 'rs48.rs4')
            ->where('rs48.rs1', $noreg)
            ->where('rs48.rs26', '<>', 'POL014');
        if ($flagruangan) {
            $radQuery->where('rs48.rs26', $flagruangan);
        }
        $radiologiTotal = (float)$radQuery->sum(DB::raw('(rs48.rs6 + rs48.rs8) * rs48.rs24'));

        $op1Query = DB::table('rs54')
            ->join('rs53', 'rs53.rs1', '=', 'rs54.rs4')
            ->where('rs54.rs1', $noreg)
            ->where('rs54.rs15', '<>', 'POL014');
        if ($flagruangan) {
            $op1Query->where('rs54.rs15', $flagruangan);
        }
        $operasi1 = (float)$op1Query->sum(DB::raw('(rs54.rs5 + rs54.rs6 + rs54.rs7) * rs54.rs8'));

        $op2Query = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', 'OPERASI');
        if ($flagruangan) {
            $op2Query->where('rs73.rs22', $flagruangan);
        }
        $operasi2 = (float)$op2Query->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));
        $operasiTotal = $operasi1 + $operasi2;

        $fisioQuery = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', 'FISIO');
        if ($flagruangan) {
            $fisioQuery->where('rs73.rs22', $flagruangan);
        }
        $fisioterapiTotal = (float)$fisioQuery->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));

        $hdQuery = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->where('rs73.rs1', $noreg)
            ->whereIn('rs73.rs22', ['POL024', 'POL026', 'PEN005']);
        if ($flagruangan) {
            $hdQuery->where('rs73.rs22', $flagruangan);
        }
        $hdCardioEegTotal = (float)$hdQuery->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));

        $penunjangLainList = [];
        $masterPenunjangLain = DB::table('rs19')->where('penunjang_lain', '1')->get();
        $totalPenunjangLainDinamis = 0;
        foreach ($masterPenunjangLain as $mpl) {
            $subMplQuery = DB::table('rs73')
                ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                ->where('rs73.rs1', $noreg)
                ->where('rs73.rs22', $mpl->rs1);
            if ($flagruangan) {
                $subMplQuery->where('rs73.rs22', $flagruangan);
            }
            $subMpl = (float)$subMplQuery->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));
            
            $penunjangLainList[] = [
                'kode' => $mpl->rs1,
                'nama' => $mpl->rs2,
                'subtotal' => $subMpl
            ];
            $totalPenunjangLainDinamis += $subMpl;
        }

        $darahQuery = DB::table('rs231')
            ->where('rs1', $noreg)
            ->where('rs14', '<>', 'POL014');
        if ($flagruangan) {
            $darahQuery->where('rs14', $flagruangan);
        }
        $darahTotal = (float)$darahQuery->sum(DB::raw('rs12 + rs13'));

        $totalPenunjang = $laboratoriumTotal + $radiologiTotal + $operasiTotal + $fisioterapiTotal + $hdCardioEegTotal + $totalPenunjangLainDinamis + $darahTotal;

        // 11. Farmasi
        $farmasiBindings = [$noreg, $noreg, $noreg, $noreg];
        $farmasiSql = "
            SELECT round(sum(subtotalx),0) as subtotal FROM (
                SELECT sum((rs38.rs6*rs38.rs8)+rs38.rs10) as subtotalx 
                FROM rs38, rs32, rs9 
                WHERE rs32.rs1=rs38.rs4 AND rs38.rs1 = ? AND rs38.lunas <> '1' 
                  AND rs9.rs1=rs38.rs21 AND rs38.rs25='CENTRAL'
                UNION ALL
                SELECT IF(rs39.rs8>1, sum((rs40.rs7*rs40.rs5)+rs39.rs8), sum((rs40.rs7*rs40.rs5))) as subtotalx 
                FROM rs39, rs40, rs32, rs9 
                WHERE rs39.rs1=rs40.rs1 AND rs32.rs1=rs40.rs4 AND rs39.rs1 = ? AND rs39.lunas <> '1' 
                  AND rs9.rs1=rs39.rs16 AND rs39.rs19='CENTRAL'
                UNION ALL					
                SELECT sum((rs62.rs6*rs62.rs8)+rs62.rs10) as subtotalx 
                FROM rs62, rs32, rs9 
                WHERE rs32.rs1=rs62.rs4 AND rs62.rs1 = ? AND rs62.lunas <> '1' 
                  AND rs9.rs1=rs62.rs21 AND rs62.rs25='CENTRAL'
                UNION ALL
                SELECT IF(rs63.rs8>1, sum((rs64.rs7*rs64.rs5)+rs63.rs8), sum((rs64.rs7*rs64.rs5))) as subtotalx 
                FROM rs63, rs64, rs32, rs9 
                WHERE rs63.rs1=rs64.rs1 AND rs32.rs1=rs64.rs4 AND rs63.rs1 = ? AND rs63.lunas <> '1' 
                  AND rs9.rs1=rs63.rs16 AND rs63.rs19='CENTRAL'
            ) as vx
        ";
        $farmasiRaw = DB::select($farmasiSql, $farmasiBindings);
        $farmasiKotor = $farmasiRaw ? (float)($farmasiRaw[0]->subtotal ?? 0) : 0;

        $returFarmasi = (float)DB::table('rs87')
            ->join('rs88', 'rs88.rs1', '=', 'rs87.rs1')
            ->join('rs32', 'rs32.rs1', '=', 'rs88.rs2')
            ->where('rs87.rs7', $noreg)
            ->sum(DB::raw('rs88.rs3 * rs88.rs4'));

        $farmasiTotal = max(0, $farmasiKotor - $returFarmasi);

        // 12. IRD
        $irdKarcis = 0;
        $adaIrd = DB::table('rs17')
            ->where('rs1', $noreg)
            ->exists();
        if ($adaIrd) {
            $irdKarcis = 8000;
        }

        $irdTindakan = (float)DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->join('rs21', 'rs21.rs1', '=', DB::raw("SUBSTRING_INDEX(rs73.rs8, ';', 1)"))
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', 'POL014')
            ->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));

        $irdLab = (float)DB::table('rs51')
            ->join('rs49', 'rs49.rs1', '=', 'rs51.rs4')
            ->where('rs51.rs1', $noreg)
            ->where('rs51.lunas', '<>', '1')
            ->where('rs51.rs23', 'POL014')
            ->sum(DB::raw('(rs51.rs6 + rs51.rs13) * rs51.rs5'));

        $irdLab2 = (float)DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', 'LAB2')
            ->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));

        $irdRadiologi = (float)DB::table('rs48')
            ->join('rs47', 'rs47.rs1', '=', 'rs48.rs4')
            ->where('rs48.rs1', $noreg)
            ->where('rs48.rs26', 'POL014')
            ->sum(DB::raw('(rs48.rs6 + rs48.rs8) * rs48.rs24'));

        $irdOperasi = (float)DB::table('rs54')
            ->join('rs53', 'rs53.rs1', '=', 'rs54.rs4')
            ->where('rs54.rs1', $noreg)
            ->where('rs54.rs15', 'POL014')
            ->sum(DB::raw('(rs54.rs5 + rs54.rs6 + rs54.rs7) * rs54.rs8'));

        $irdOperasi2 = (float)DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->where('rs73.rs1', $noreg)
            ->where('rs73.rs22', 'OPERASI2')
            ->sum(DB::raw('(rs73.rs7 + rs73.rs13) * rs73.rs5'));

        $irdTotal = $irdKarcis + $irdTindakan + $irdLab + $irdLab2 + $irdRadiologi + $irdOperasi + $irdOperasi2;

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

        $response = [
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
                'tglkeluar' => ($pasien->tglkeluar && $pasien->tglkeluar !== '0000-00-00 00:00:00') ? $pasien->tglkeluar : '0000-00-00 00:00:00',
                'dokter' => $pasien->dokter ?? '',
                'sistembayar' => $sistembayarNama,
                'filter' => [
                    'flagruangan' => $flagruangan,
                    'flagsistembayar' => $flagsistembayar,
                    'flagruangankelas' => $flagruangankelas,
                ],
                'list_ruangan' => $listRuangan,
                'list_sistembayar' => $listSistemBayar,
            ],
            'rincian' => [
                'administrasi' => $administrasi,
                'akomodasi' => [
                    'total' => $akomodasiTotal,
                    'items' => $akomodasiList
                ],
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
                    'operasi' => $operasiTotal,
                    'fisioterapi' => $fisioterapiTotal,
                    'hemodialisa_cardio_eeg' => $hdCardioEegTotal,
                    'penunjang_lain' => $penunjangLainList,
                    'penggunaan_darah' => $darahTotal
                ],
                'farmasi' => $farmasiTotal,
                'ird' => $irdTotal
            ],
            'grand_total' => $grandTotal
        ];

        return new JsonResponse(['result' => $response], 200);
    }

    /**
     * Get Faktur Detail Rawat Inap
     * Mengadopsi 100% logika fakturdetail.php legacy
     */
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

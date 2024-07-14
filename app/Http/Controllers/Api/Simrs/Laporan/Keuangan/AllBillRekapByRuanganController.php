<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllBillRekapByRuanganController extends Controller
{
    public function allBillRekapByRuangan()
    {
        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';

        $data = Kunjunganranap::select('rs1','rs2','rs3','rs4','rs5','rs19','titipan')
        ->with(
            [
                'rstigalimax' => function ($rstigalimax) {
                    $rstigalimax->select('rs1','rs4', 'rs7', 'rs14', 'rs17')->where('rs3', 'K1#')->orderBy('rs4', 'DESC');
                },
                'akomodasikamar' => function ($akomodasikamar) {
                    $akomodasikamar->select('rs1', 'rs7', 'rs14','rs16')->where('rs3', 'K1#')->orderBy('rs4', 'DESC');
                },
                'biayamaterai' => function ($biayamaterai) {
                    $biayamaterai->select('rs1', 'rs5')->where('rs7', '!=', 'IRD');
                },
                'tindakandokter' => function ($tindakandokterperawat) {
                    $tindakandokterperawat->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs22')
                        ->join('rs24', 'rs24.rs4', '=', 'rs73.rs22')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->where('rs21.rs13', '1')
                        ->groupBy('rs24.rs4', 'rs73.rs2', 'rs73.rs4');
                    //->where('rs73.rs22','POL014');
                },
                'visiteumum' => function ($visiteumum) {
                    $visiteumum->select('rs1', 'rs4', 'rs5','rs8');
                },
                'tindakanperawat' => function ($tindakanperawat) {
                    $tindakanperawat->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs22')
                        ->join('rs24', 'rs24.rs4', '=', 'rs73.rs22')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->where('rs21.rs13', '!=', '1')
                        ->groupBy('rs24.rs4', 'rs73.rs2', 'rs73.rs4', 'rs73.id');
                    //->where('rs73.rs22','POL014');
                },
                'makanpasien' => function ($makanpasien) {
                    $makanpasien->select('rs1', 'rs4', 'rs5','rs8')->whereIn('rs3', ['K00003', 'K00004']);
                    //$makanpasien->select('rs1','rs4','rs5')->where('rs3','K00003')->orWhere('rs3','K00004');
                },
                'oksigen' => function ($oksigen) {
                    $oksigen->select('rs1', 'rs4', 'rs5', 'rs6','rs8');
                },
                'keperawatan' => function ($keperawatan) {
                    $keperawatan->select('rs1', 'rs4', 'rs5','rs8');
                },
                'laborat' => function ($laborat) {
                    $laborat->select('rs51.rs1', 'rs51.rs2 as nota', 'rs51.rs4 as kode', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket', 'rs51.rs23 as ruangan',
                        DB::raw('round((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
                        ->leftjoin('rs49', 'rs51.rs4', '=', 'rs49.rs1')
                        ->where('rs51.rs23','!=','POL014')->where('rs49.rs21','!=','')
                        ->groupBy( 'rs51.rs2', 'rs49.rs21');
                },
                'laboratnonpaket' => function ($laborat) {
                    $laborat->select('rs51.rs1', 'rs51.rs2 as nota', 'rs51.rs4 as kode', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket', 'rs51.rs23 as ruangan',
                        DB::raw('round((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
                        ->leftjoin('rs49', 'rs51.rs4', '=', 'rs49.rs1')
                        ->where('rs51.rs23','!=','POL014')->where('rs49.rs21','=','')
                        //->groupBy( 'rs51.rs2')
                        ;
                },
                // 'laborat.pemeriksaanlab:rs1,rs2,rs21',
                'transradiologi' => function ($transradiologi) {
                    $transradiologi->select('rs48.rs1', 'rs48.rs6', 'rs48.rs8', 'rs48.rs24','rs48.rs26','rs24.rs5')
                        ->join('rs24', 'rs24.rs4', '=', 'rs48.rs26')
                        ->where('rs48.rs26','!=','POL014')
                        ->groupBy('rs24.rs4', 'rs48.rs2', 'rs48.rs4');
                },
                'tindakanendoscopy' => function ($tindakanendoscopy) {
                    $tindakanendoscopy->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs73.rs16','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs73.rs22', 'POL031')
                    ->where('rs73.rs16','!=','POL014');
                },
                'kamaroperasiibs' => function ($kamaroperasiibs) {
                    $kamaroperasiibs->select('rs54.rs1', 'rs54.rs5', 'rs54.rs6', 'rs54.rs7', 'rs54.rs8','rs54.rs15')
                        ->join('rs24', 'rs24.rs4', '=', 'rs54.rs15')
                        ->where('rs54.rs15','!=', 'POL014')
                        ->groupBy('rs54.rs2', 'rs54.rs4');;
                },
                'kamaroperasiigd' => function ($kamaroperasiigd) {
                    $kamaroperasiigd->select('rs226.rs1', 'rs226.rs5', 'rs226.rs6', 'rs226.rs7', 'rs226.rs8','rs226.rs15')
                        ->join('rs24', 'rs24.rs4', '=', 'rs226.rs15')
                        ->where('rs226.rs15','!=', 'POL014')
                        ->groupBy('rs226.rs2', 'rs226.rs4');
                },
                'tindakanoperasi' => function ($tindakanoperasi) {
                    $tindakanoperasi->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs73.rs22', 'OPERASI')
                    ->where('rs73.rs16','!=','POL014');
                },
                'tindakanoperasiigd' => function ($tindakanoperasiigd) {
                    $tindakanoperasiigd->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs73.rs22', 'OPERASIIRD')
                    ->where('rs73.rs16','!=','POL014');
                },
                'tindakanfisioterapi' => function ($tindakanfisioterapi) {
                    $tindakanfisioterapi->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs73.rs22', 'FISIO')->OrWhere('rs22', 'PEN005')
                    ->where('rs73.rs16','!=','POL014');
                },
                // 'tindakanfisioterapi' => function ($tindakanfisioterapi) {
                //     $tindakanfisioterapi->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                //     ->join('rs24','rs24.rs1','rs73.rs16')
                //     ->where('rs22', 'PEN005')
                //     ->where('rs73.rs16','!=','POL014');
                // },
                'tindakananastesidiluarokdanicu' => function ($tindakananastesidiluarokdanicu) {
                    $tindakananastesidiluarokdanicu->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs22', 'PEN012')
                    ->where('rs25', '!=', 'POL014');
                },
                'tindakancardio' => function ($tindakancardio) {
                    $tindakancardio->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs22', 'POL026')
                    ->where('rs25', '!=', 'POL014');
                },
                'tindakaneeg' => function ($tindakaneeg) {
                    $tindakaneeg->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs22', 'POL024')
                    ->where('rs25', '!=', 'POL014');
                },
                 'psikologtransumum' => function ($psikologtransumum) {
                    $psikologtransumum->select('psikologi_trans.rs1','psikologi_trans.rs2','psikologi_trans.rs3','psikologi_trans.rs4 as kodetindakan','psikologi_trans.rs7','psikologi_trans.rs13',
                    'psikologi_trans.rs5','rs24.rs4')
                    ->leftjoin('rs24','psikologi_trans.rs25','rs24.rs4')
                    ->where('rs24.rs1','!=','')
                    ->groupBy('psikologi_trans.rs2','psikologi_trans.rs4');
                 },
                'bdrs' => function ($bdrs) {
                    $bdrs->select('rs1', 'rs12', 'rs13','rs14')->where('rs14', '!=', 'POL014');
                },
                 'penunjangkeluar' => function ($penunjangkeluar) {
                    $penunjangkeluar->select('noreg','nota','ruangan','harga_sarana','harga_pelayanan','jumlah')
                    ->where('ruangan','!=','POL014');
                 },
                // 'apotekranap' => function ($apotekranap) {
                //     $apotekranap->select('rs1', 'rs6', 'rs8', 'rs10')->where('rs20', '!=', 'POL014')->where('lunas', '!=', '1')
                //         ->where('rs25', 'CENTRAL');
                // },
                // 'apotekranaplalu' => function ($apotekranaplalu) {
                //     $apotekranaplalu->select('rs1', 'rs6', 'rs8', 'rs10')->where('rs20', '!=', 'POL014')->where('lunas', '!=', '1')
                //         ->where('rs25', 'CENTRAL');
                // },
                // 'apotekranapracikanheder' => function ($apotekranapracikanheder) {
                //     $apotekranapracikanheder->select('rs1', 'rs8')->where('lunas', '!=', '1')->where('rs19', 'CENTRAL')->Where('rs18', '!=', 'IGD');
                // },
                // 'apotekranapracikanrinci:rs1,rs5,rs7',
                // 'apotekranapracikanhederlalu' => function ($apotekranapracikanhederlalu) {
                //     $apotekranapracikanhederlalu->select('rs1', 'rs8')->where('lunas', '!=', '1')->where('rs19', 'CENTRAL')->Where('rs18', '!=', 'IGD');
                // },
                // 'apotekranapracikanrincilalu:rs1,rs5,rs7',
                // 'kamaroperasiibsx' => function ($kamaroperasiibsx) {
                //     $kamaroperasiibsx->select('rs1', 'rs5', 'rs6', 'rs7', 'rs8')
                //         ->where('rs15', 'POL014');
                // },
                // 'tindakanoperasix' => function ($tindakanoperasix) {
                //     $tindakanoperasix->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'OPERASI2');
                // },
                // 'ambulan' => function ($ambulan) {
                //     $ambulan->select('rs1', 'rs2', 'rs15', 'rs16', 'rs17', 'rs18', 'rs23', 'rs26', 'rs30')->where('rs20', '!=', 'POL014');
                // },

                // //------------------igd-------------//

                // 'rstigalimaxxx' => function ($rstigalimaxxx) {
                //     $rstigalimaxxx->select('rs1', 'rs6', 'rs7')->where('rs3', 'A2#');
                // },
                // 'irdtindakan' => function ($irdtindakan) {
                //     $irdtindakan->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'POL014');
                // },
                // 'laboratdiird' => function ($laboratdiird) {
                //     $laboratdiird->select('rs1', 'rs2', 'rs3', 'rs4', 'rs5', 'rs6', 'rs13', 'rs23')->where('rs23', 'POL014')->where('rs18', '!=', '')
                //         ->where('rs23', '!=', '1');
                // },
                // 'laboratdiird.pemeriksaanlab:rs1,rs2,rs21',
                // 'transradiologidiird' => function ($transradiologidiird) {
                //     $transradiologidiird->select('rs1', 'rs6', 'rs8', 'rs24')->where('rs26', 'POL014');
                // },
                // 'irdtindakanoperasix' => function ($irdtindakanoperasix) {
                //     $irdtindakanoperasix->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'OPERASIIRD2');
                // },
                // 'irdkamaroperasiigd' => function ($irdkamaroperasiigd) {
                //     $irdkamaroperasiigd->select('rs226.rs1', 'rs226.rs5', 'rs226.rs6', 'rs226.rs7', 'rs226.rs8')
                //         ->where('rs226.rs15', 'POL014');
                // },
                // 'irdbdrs' => function ($irdbdrs) {
                //     $irdbdrs->select('rs1', 'rs12', 'rs13')->where('rs14', 'POL014');
                // },
                // 'irdbiayamaterai' => function ($irdbiayamaterai) {
                //     $irdbiayamaterai->select('rs1', 'rs5')->where('rs7', 'IRD');
                // },
                // 'irdambulan' => function ($irdambulan) {
                //     $irdambulan->select('rs1', 'rs2', 'rs15', 'rs16', 'rs17', 'rs18', 'rs23', 'rs26', 'rs30')->where('rs20', 'POL014');
                // },
                // 'irdtindakanhd' => function ($irdtindakanhd) {
                //     $irdtindakanhd->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'PEN005')->where('rs25', 'POL014');
                // },
                // 'irdtindakananastesidiluarokdanicu' => function ($irdtindakananastesidiluarokdanicu) {
                //     $irdtindakananastesidiluarokdanicu->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'PEN012')->where('rs25', 'POL014');
                // },
                // 'irdtindakanfisioterapi' => function ($irdtindakanfisioterapi) {
                //     $irdtindakanfisioterapi->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'fisioterapi')->where('rs25', 'POL014');
                // },
                // 'apotekranapx' => function ($apotekranap) {
                //     $apotekranap->select('rs1', 'rs6', 'rs8', 'rs10')->where('rs20', 'POL014')->where('lunas', '!=', '1')
                //         ->where('rs24', 'IRD')->where('rs25', 'CENTRAL')->orWhere('rs25', 'IGD');
                // },
                // 'apotekranaplalux' => function ($apotekranaplalux) {
                //     $apotekranaplalux->select('rs1', 'rs6', 'rs8', 'rs10')->where('rs20', 'POL014')->where('lunas', '!=', '1')
                //         ->where('rs24', 'IRD')->where('rs25', 'CENTRAL')->orWhere('rs25', 'IGD');
                // },
                // 'apotekranapracikanhederx' => function ($apotekranapracikanhederx) {
                //     $apotekranapracikanhederx->select('rs1', 'rs8')->where('lunas', '!=', '1')
                //         ->where('rs18', 'IRD')->where('rs19', 'CENTRAL')->orWhere('rs18', 'IGD');
                // },
                // 'apotekranapracikanrincix:rs1,rs5,rs7',
                // 'apotekranapracikanhederlalux' => function ($apotekranapracikanhederlalux) {
                //     $apotekranapracikanhederlalux->select('rs1', 'rs8')->where('lunas', '!=', '1')
                //         ->where('rs18', 'IRD')->where('rs19', 'CENTRAL')->orWhere('rs18', 'IGD');
                // },
                // 'apotekranapracikanrincilalux:rs1,rs5,rs7',
                // 'groupingranap:noreg,nosep,cbg_code,cbg_desc,cbg_tarif,procedure_tarif,prosthesis_tarif,investigation_tarif,drug_tarif,acute_tarif,chronic_tarif',
                // 'klaimranap:noreg,nama_dokter'
            ]
        )
        ->whereBetween('rs23.rs4', [$dari, $sampai])
        ->get();

        // $data = Rstigalimax::select('rs23.rs1','rs23.rs2','rs35x.rs16','rs24.rs5',
        // DB::raw('sum(rs35x.rs7+rs35x.rs14) as subtotalx'),DB::raw('count(rs35x.rs1) as lama'))
        // ->with(
        //     [
        //         'kunjunganranap' => function ($kunjungan) {
        //             $kunjungan->with(
        //                 [
        //                     'rstigalimax' => function ($biayaadmin) {
        //                         $biayaadmin->select(DB::raw('sum(rs7+rs14) as subtotalx'))->where('rs3', 'K1#');
        //                     }
        //                 ]
        //             );
        //         }
        //     ])
        // ->leftjoin('rs23','rs23.rs1','rs35x.rs1')
        // ->leftjoin('rs24','rs24.rs1','rs35x.rs18')
        // ->where('rs35x.rs3','K1#')->whereBetween('rs23.rs4', [$dari, $sampai])
        // ->groupBy('rs35x.rs1','rs24.rs4')
        // ->get();
        return new JsonResponse($data);
    }
}

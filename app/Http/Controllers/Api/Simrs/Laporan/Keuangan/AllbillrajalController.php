<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Billing\Rajal\Allbillrajal;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllbillrajalController extends Controller
{
    public function kumpulanbillpasien()
    {
        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';
        $allbillrajal = Allbillrajal::select('rs1','rs2','rs3','rs8','rs14')->with([
        'masterpasien:rs1,rs2',
        'relmpoli:rs1,rs2',
        'msistembayar:rs1,rs2',
        'apotekrajalpolilalu:rs1,rs2,rs3,rs4,rs6,rs8,rs10',
        'apotekrajalpolilalu.mobat:rs1,rs2',
        'apotekracikanrajal.relasihederracikan:rs1,rs2,rs8',
        'apotekracikanrajal.racikanrinci:rs1,rs2',
        'laborat:id,rs1,rs2,rs3,rs4,rs5,rs6,rs13',
        'laborat.pemeriksaanlab:rs1,rs2,rs21',
        'radiologi',
        'radiologi.reltransrinci',
        'radiologi.reltransrinci.relmasterpemeriksaan'
       ])
        ->whereBetween('rs3', [$dari, $sampai])
        ->where('rs8','!=','POL014')->where('rs8','!=','PEN004')->where('rs8','!=','PEN005')
        ->where('rs19','=','1')
        ->get();

        // $colection = collect($kunjunganpoli);
        // $farmasi = $colection->filter(function ($value, $key) {
        //     return $value['apotekrajallalu']!==null;
        // });
        return new JsonResponse($allbillrajal);
    }

    public function rekapanbill()
    {
        $layanan = request('layanan');
        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';

        if($layanan === '1')
        {
            $allbillrajal = Allbillrajal::select('rs1','rs2','rs3','rs8','rs14')->with([
                'masterpasien:rs1,rs2',
                'relmpoli:rs1,rs2',
                'msistembayar:rs1,rs2',
                'biayarekammedik' => function($biayarekammedik){
                    $biayarekammedik->select('rs1','rs2','rs6','rs7','rs11')->where('rs3','RM#');
                },
                'biayakartuidentitas' => function($biayakartuidentitas){
                    $biayakartuidentitas->select('rs1','rs2','rs6','rs7','rs11')->where('rs3','K1#');
                },
                'biayapelayananpoli' => function($biayapelayananpoli){
                    $biayapelayananpoli->select('rs1','rs2','rs6','rs7','rs11')->where('rs3','K2#');
                },
                'biayakonsulantarpoli' =>function($biayakonsulantarpoli){
                    $biayakonsulantarpoli->select('rs1','rs2','rs6','rs7','rs11')->where('rs3','K3#');
                },
                'tindakandokterperawat' => function($tindakandokterperawat){
                    // $tindakandokterperawat ->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','!=','POL014')
                    // ->where('rs22','!=','POL024')->where('rs22','!=','POL026')->where('rs22','!=','POL029')
                    // ->where('rs22','!=','POL030')->where('rs22','!=','POL031')->where('rs22','!=','POL036');
                    $poli = ['POL014','POL024','POL026','POL029','POL030','POL031','POL036'];
                    $tindakandokterperawat ->select('rs1','rs2','rs7','rs13','rs5')->whereNotIn('rs22', $poli);
                },
                'visiteumum' => function($visiteumum){
                    $visiteumum->select('rs1','rs4','rs5');
                },
                'laborat:id,rs1,rs2,rs3,rs4,rs5,rs6,rs13',
                'laborat.pemeriksaanlab:rs1,rs2,rs21',
                'radiologi',
                'radiologi.reltransrinci',
                'radiologi.reltransrinci.relmasterpemeriksaan',
                'kamaroperasi',
                'tindakanoperasi' => function($tindakanoperasi){
                    $tindakanoperasi->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','OPERASI');
                },
                'tindakanendoscopy' => function($tindakanendoscopy){
                    $tindakanendoscopy->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL031');
                },
                'tindakanfisioterapi' => function($tindakanfisioterapi){
                    $tindakanfisioterapi->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','fisioterapi');
                },
                'tindakanhd' => function($tindakanhd){
                    $tindakanhd->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','PEN005');
                },
                'tindakananastesidiluarokdanicu' => function($tindakananastesidiluarokdanicu){
                    $tindakananastesidiluarokdanicu->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','PEN012');
                },
                'psikologtransumum',
                'tindakancardio' => function($tindakancardio){
                    $tindakancardio->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL026');
                },
                'tindakaneeg' => function($tindakaneeg){
                    $tindakaneeg->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL024');
                },
                'apotekrajalpolilalu:rs1,rs2,rs3,rs4,rs6,rs8,rs10',
                'apotekracikanrajal.relasihederracikan:rs1,rs2,rs8',
                'apotekracikanrajal.racikanrinci:rs1,rs2',
                'pendapatanallbpjs:noreg,cbg_tarif,procedure_tarif,prosthesis_tarif,investigation_tarif,drug_tarif,acute_tarif,chronic_tarif'
                ])
                ->whereBetween('rs3', [$dari, $sampai])
                ->where('rs8','!=','POL014')->where('rs8','!=','PEN004')->where('rs8','!=','PEN005')
                ->where('rs19','=','1')
                ->get();
                return new JsonResponse($allbillrajal);
        }elseif($layanan === '2'){
            $allbillrajal = Allbillrajal::select('rs17.rs1','rs17.rs2','rs17.rs3','rs17.rs8','rs17.rs14')->with([
                    'masterpasien:rs1,rs2',
                    'relmpoli:rs1,rs2',
                    'msistembayar:rs1,rs2',
                    'administrasiigd' => function($administrasiigd){
                        $administrasiigd->select('rs1','rs7')->where('rs3','A2#');
                    },
                    'tindakandokterperawat' => function($tindakandokterperawat){
                        $tindakandokterperawat->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL014');
                    },
                    'laborat' => function($laborat){
                        $laborat->select('rs1','rs2','rs3','rs4','rs5','rs6','rs13','rs23')->where('rs23','POL014')->where('rs18','!=','')
                        ->where('rs23','!=','1');
                    },
                    'laborat.pemeriksaanlab:rs1,rs2,rs21',
                    'transradiologi' => function($transradiologi){
                        $transradiologi->select('rs1','rs6','rs8','rs24')->where('rs26','POL014');
                    },
                    // 'radiologi.reltransrinci',
                    // 'radiologi.reltransrinci.relmasterpemeriksaan',
                    'tindakanfisioterapi' => function($tindakanfisioterapi){
                        $tindakanfisioterapi->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','fisioterapi');
                    },
                    'tindakanhd' => function($tindakanhd){
                        $tindakanhd->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','PEN005')->where('rs25','POL014');
                    },
                    'tindakananastesidiluarokdanicu' => function($tindakananastesidiluarokdanicu){
                        $tindakananastesidiluarokdanicu->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','PEN012')->where('rs25','POL014');
                    },
                    'tindakancardio' => function($tindakancardio){
                        $tindakancardio->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL026');
                    },
                    'tindakaneeg' => function($tindakaneeg){
                        $tindakaneeg->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL024');
                    },
                    'bdrs' => function($bdrs){
                        $bdrs->select('rs1','rs12','rs13')->where('rs14','POL014');
                    },
                    'tindakanendoscopy' => function($tindakanendoscopy){
                        $tindakanendoscopy->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL031');
                    },
                    'okigd' => function($okigd){
                        $okigd->select('rs1','rs5','rs6','rs7')->where('rs15','POL014');
                    },
                    'tindakokigd' => function($tindakokigd){
                        $tindakokigd->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','OPERASIIRD2');
                    },
                    'kamaroperasi' => function($kamaroperasi){
                        $kamaroperasi->select('rs1','rs5','rs6','rs7','rs8')->where('rs15','POL014');
                    },
                    'tindakanoperasi' => function($tindakanoperasi){
                        $tindakanoperasi->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','OPERASI2');
                    },
                    'kamarjenasah' => function($kamarjenasah){
                        $kamarjenasah->select('rs1','rs6','rs7')->where('rs14','POL014');
                    },
                    'kamarjenasahinap' => function($kamarjenasahinap){
                        $kamarjenasahinap->select('rs1','rs5','rs6')->where('rs7','POL014');
                    },
                    'ambulan' => function($ambulan){
                        $ambulan->select('rs1','rs2','rs15','rs16','rs17','rs18','rs23','rs26','rs30')->where('rs20','POL014');
                    },
                    'apotekranap' => function($apotekranap){
                        $apotekranap->select('rs1','rs6','rs8','rs10')->where('rs20','POL014')->where('lunas','!=','1')
                        ->where('rs25','CENTRAL')->orWhere('rs25','IGD');
                    },
                    'apotekranaplalu' => function($apotekranaplalu){
                        $apotekranaplalu->select('rs1','rs6','rs8','rs10')->where('rs20','POL014')->where('lunas','!=','1')
                        ->where('rs25','CENTRAL')->orWhere('rs25','IGD');
                    },
                    'apotekranapracikanheder' => function($apotekranapracikanheder){
                        $apotekranapracikanheder->select('rs1','rs8')->where('lunas','!=','1')->where('rs19','CENTRAL')->orWhere('rs19','IGD');
                    },
                    'apotekranapracikanrinci:rs1,rs5,rs7',
                    'apotekranapracikanhederlalu' => function($apotekranapracikanhederlalu){
                        $apotekranapracikanhederlalu->select('rs1','rs8')->where('lunas','!=','1')->where('rs19','CENTRAL')->orWhere('rs19','IGD');
                    },
                    'apotekranapracikanrincilalu:rs1,rs5,rs7',
                    'biayamaterai' => function($biayamaterai){
                        $biayamaterai->select('rs1','rs5')->where('rs7','IRD');
                    },
                    'pendapatanallbpjs:noreg,cbg_tarif,procedure_tarif,prosthesis_tarif,investigation_tarif,drug_tarif,acute_tarif,chronic_tarif'
                ])
                ->leftjoin('rs141','rs141.rs1','=', 'rs17.rs1')
                ->whereBetween('rs17.rs3', [$dari, $sampai])
                ->where('rs17.rs8','POL014')
                ->where('rs17.rs19','=','1')
                ->where('rs141.rs4', '!=', 'Rawat Inap')
                ->get();
                return new JsonResponse($allbillrajal);
        }else{
            $allbillrajal = Kunjunganranap::select('rs1','rs2','rs3','rs4','rs5','rs19')->with([
                'masterpasien:rs1,rs2',
                'relmasterruangranap:rs1,rs2',
                'relsistembayar:rs1,rs2',
                'rstigalimax' => function($rstigalimax){
                    $rstigalimax->select('rs1','rs8','rs17','rs4')->where('rs3','K1#')->orderBy('rs4', 'DESC');
                },

            ])
            ->whereBetween('rs3', [$dari, $sampai])
            ->get();
            $ee = $allbillrajal->map(function ($query) {
                $query->setRelation('rstigalimax', $query->rstigalimax->take(1));
                return $query;
                // $kelas = $query->rstigalimax[0]->rs17;
                // if($kelas === '3'){
                //   $admin =  Rstigapuluhtarif::select()
            });
            $aa=$ee->map(function ($query) {
                $admin = $query->rstigalimax[0]->rs17;
                $administrasi=0;
                $tarif = Rstigapuluhtarif::where('rs3', 'A1#')->first();
                if($admin==="3"){
                    $administrasi=$tarif->rs6 + $tarif->rs7;
                }else if($admin==="2"){
                    $administrasi=$tarif->rs8 + $tarif->rs9;
                }else if($admin==="1" || $admin==="IC" || $admin==="ICC" || $admin==="NICU" || $admin==="IN"){
                    $administrasi=$tarif->rs10 + $tarif->rs11;
                }else if($admin==="Utama"){
                    $administrasi=$tarif->rs12+$tarif->rs13;
                }else if($admin==="VIP"){
                    $administrasi=$tarif->rs14+$tarif->rs15;
                }else if($admin==="VVIP"){
                    $administrasi= $tarif->rs16+ $tarif->rs17;
                }

                $query['admin']=$administrasi;
                return $query;
            });
            return new JsonResponse($aa);
        }
    }
}

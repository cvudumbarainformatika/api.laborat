<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Billing\Rajal\Allbillrajal;
use App\Models\Simrs\Rajal\KunjunganPoli;
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
                    $tindakandokterperawat ->select('rs1','rs2','rs7','rs13','rs5')->where('rs22','POL001')
                    ->orWhere('rs22','POL002')->orWhere('rs22','POL003')->orWhere('rs22','POL004')
                    ->orWhere('rs22','POL005')->orWhere('rs22','POL006')->orWhere('rs22','POL007')
                    ->orWhere('rs22','POL008')->orWhere('rs22','POL009')->orWhere('rs22','POL010')
                    ->orWhere('rs22','POL011')->orWhere('rs22','POL012')->orWhere('rs22','POL013')
                    ->orWhere('rs22','POL015')->orWhere('rs22','POL016')->orWhere('rs22','POL017')
                    ->orWhere('rs22','POL018')->orWhere('rs22','POL019')->orWhere('rs22','POL020')
                    ->orWhere('rs22','POL021')->orWhere('rs22','POL022')
                    ->orWhere('rs22','POL023')->orWhere('rs22','POL025')->orWhere('rs22','POL027')
                    ->orWhere('rs22','POL032')->orWhere('rs22','POL034')->orWhere('rs22','POL035')
                    ->orWhere('rs22','POL039')->orWhere('rs22','POL038')->orWhere('rs22','POL040');
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
                'pendapatanallbpjs:noreg,konsultasi,tenaga_ahli,keperawatan,penunjang,radiologi,Pelayanan_darah,rehabilitasi,kamar,rawat_intensif,obat,alkes,bmhp,sewa_alat,tarif_poli_eks,delete_status,status_klaim'
                ])
                ->whereBetween('rs3', [$dari, $sampai])
                ->where('rs8','!=','POL014')->where('rs8','!=','PEN004')->where('rs8','!=','PEN005')
                ->where('rs19','=','1')
                ->get();
                return new JsonResponse($allbillrajal);
        }elseif('2'){
            $allbillrajal = Allbillrajal::select('rs1','rs2','rs3','rs8','rs14')->with([
                    'masterpasien:rs1,rs2',
                    'relmpoli:rs1,rs2',
                    'msistembayar:rs1,rs2',
                    'administrasiigd' => function($administrasiigd){
                        $administrasiigd->select('rs1','rs7')->where('rs3','A2#');
                    }
                ])
                ->whereBetween('rs3', [$dari, $sampai])
                ->where('rs8','POL014')
                ->where('rs19','=','1')
                ->get();
                return new JsonResponse($allbillrajal);
        }else{
            return('wew');
        }
    }
}

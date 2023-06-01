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
        'radiologi.relmasterpemeriksaan:rs1,rs2'
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
}

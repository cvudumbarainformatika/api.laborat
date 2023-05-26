<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
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
        $kunjunganpoli = KunjunganPoli::with([
        'masterpasien:rs1,rs2'])
        //->select('rs1 as noreg','rs2 as norm')
        ->whereBetween('rs3', [$dari, $sampai])
        ->where('rs8','!=','POL014')
        ->where('rs19','=','1')
        ->get();
        return new JsonResponse($kunjunganpoli);
    }
}
